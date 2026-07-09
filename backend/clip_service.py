"""
Servidor API Flask - Motor de Búsqueda Visual con OpenAI CLIP.
Aplica el principio de Responsabilidad Única (SRP) abstrayendo la inferencia de IA 
de la lógica de almacenamiento de la base de datos.
"""

import io
import json
import base64
from concurrent.futures import ThreadPoolExecutor

import numpy as np
import requests
import torch
import torch.nn.functional as F
from flask import Flask, jsonify, request
from flask_cors import CORS
from PIL import Image
from transformers import CLIPModel, CLIPProcessor

# ==============================================================================
# CONFIGURACIÓN Y CONSTANTES
# ==============================================================================
LARAVEL_URL = "http://127.0.0.1:3000"
HTTP_TIMEOUT = 30
MAX_WORKERS  = 10  # Hilos concurrentes optimizados para la descarga del catálogo inicial

app = Flask(__name__)
CORS(app)  # Habilita Cross-Origin Resource Sharing para consumo del frontend

# ==============================================================================
# INICIALIZACIÓN DEL MODELO (Patrón Strategy implícito en la vectorización)
# ==============================================================================
# Selección dinámica de hardware (Aceleración por GPU CUDA si está disponible)
device = "cuda" if torch.cuda.is_available() else "cpu"
print(f"🖥️  Dispositivo de cómputo seleccionado: {device}")

print("⏳ Cargando topología y pesos del modelo CLIP desde HuggingFace...")
model     = CLIPModel.from_pretrained("openai/clip-vit-base-patch32").to(device).eval()
processor = CLIPProcessor.from_pretrained("openai/clip-vit-base-patch32")
print("✅ Modelo CLIP inicializado correctamente en modo evaluación.")

print("🔥 Calentando el modelo (primera inferencia siempre es más lenta)...")
_t0 = __import__("time").time()
_ = model.get_image_features(**processor(images=Image.new("RGB", (224, 224)), return_tensors="pt").to(device))
print(f"✅ Modelo calentado en {__import__('time').time() - _t0:.2f}s. Las búsquedas reales ya no pagan ese costo.")

# Base de vectores indexada en memoria intermedia (Ram-Cache)
# Estructura esperada: list[dict] -> [{"id": int, "vector": np.ndarray}]
vector_database = []


# ==============================================================================
# FUNCIONES AUXILIARES (HELPERS)
# ==============================================================================
MAX_DIM = 512  # Tamaño máximo (px) antes de pasar la imagen al modelo.


def _redimensionar(image: Image.Image, max_dim: int = MAX_DIM) -> Image.Image:
    """
    Reduce fotos grandes (ej. 4000x3000 de un celular) ANTES de tocar el modelo.
    CLIP internamente termina usando 224x224 de todas formas, así que decodificar
    y procesar la imagen a su resolución original es tiempo de CPU desperdiciado.
    Esta es la optimización de mayor impacto para bajar el tiempo de búsqueda.
    """
    if max(image.size) <= max_dim:
        return image
    ratio = max_dim / max(image.size)
    nuevo_size = (int(image.width * ratio), int(image.height * ratio))
    return image.resize(nuevo_size, Image.BILINEAR)


def get_embedding(image: Image.Image) -> np.ndarray:
    """
    Estrategia de extracción de características vectoriales de una imagen.
    Garantiza la normalización L2 para permitir búsquedas por producto punto (Similitud Coseno).

    :param image: Instancia de una imagen PIL en formato RGB.
    :return: Array de NumPy unidimensional con el vector de características.
    """
    image = _redimensionar(image)

    # Preprocesamiento de imagen y transferencia al dispositivo de cómputo (CPU/GPU)
    inputs = processor(images=image, return_tensors="pt").to(device)
    
    with torch.no_grad():  # Desactiva el cálculo de gradientes para optimizar RAM/VRAM
        outputs = model.get_image_features(**inputs)
        # Soporte multi-versión para la librería transformers de HuggingFace
        features = outputs.pooler_output if hasattr(outputs, "pooler_output") else outputs
        # Normalización Euclidiana (L2). Convierte la similitud de coseno en un producto punto directo
        features = F.normalize(features, p=2, dim=-1)
        
    return features.cpu().numpy()[0]


def procesar_imagen(par: tuple) -> dict | None:
    """
    Descarga UNA imagen de referencia y calcula su embedding. Recibe una tupla
    (place_id, url). Cada lugar puede tener varias imágenes → varios vectores,
    todos apuntando al mismo place_id. Así se reconoce el lugar desde más ángulos.
    """
    place_id, url = par
    if not url:
        return None

    # Normalización de URLs relativas provenientes del ecosistema Laravel
    if not url.startswith("http"):
        url = LARAVEL_URL + ("" if url.startswith("/") else "/") + url

    try:
        resp = requests.get(url, timeout=HTTP_TIMEOUT)
        if resp.status_code == 200:
            img = Image.open(io.BytesIO(resp.content)).convert("RGB")
            return {"id": int(place_id), "vector": get_embedding(img)}
    except Exception as e:
        print(f"  ⚠️ Error de indexación (lugar {place_id}, {url}): {e}")

    return None


def construir_cache():
    """
    Sincroniza y re-indexa el catálogo completo de vectores desde el endpoint de Laravel.
    Utiliza concurrencia por hilos para mitigar el cuello de botella de I/O de red.
    """
    global vector_database
    # Catálogo multi-imagen: cada lugar con TODAS sus fotos de referencia.
    url = f"{LARAVEL_URL}/api/clip-catalog"
    print(f"🔄 Sincronizando catálogo desde {url} ...")
    try:
        try:
            resp = requests.get(url, timeout=HTTP_TIMEOUT)
        except requests.exceptions.RequestException as conn_err:
            print("❌ No se pudo conectar con Laravel. ¿Está corriendo en el puerto 3000?")
            print(f"   (php artisan serve --port=3000)  Detalle: {conn_err}")
            return

        if resp.status_code != 200:
            print(f"❌ Sincronización abortada. Laravel devolvió código HTTP {resp.status_code}.")
            print(f"   Cuerpo recibido (primeros 200 caracteres): {resp.text[:200]!r}")
            return

        cuerpo = resp.content.decode("utf-8-sig").strip()
        if not cuerpo:
            print("❌ Laravel respondió 200 pero con cuerpo VACÍO. Revisa /api/tourist-places.")
            return

        try:
            lugares = json.loads(cuerpo)
        except json.JSONDecodeError:
            # No era JSON (probablemente una página de error HTML de Laravel)
            print("❌ La respuesta NO es JSON válido. Esto suele ser una página de error de Laravel.")
            print(f"   Abre en el navegador: {url}")
            print(f"   Cuerpo recibido (primeros 300 caracteres): {cuerpo[:300]!r}")
            return

        # La API puede devolver {"data": [...]} (paginado) o directamente [...]
        if isinstance(lugares, dict):
            lugares = lugares.get("data", [])

        # Construimos la lista de pares (place_id, url) con TODAS las imágenes.
        # Compatibilidad: si un lugar trae 'images' (clip-catalog) usamos esa lista;
        # si no, caemos al campo 'imagen_url' antiguo.
        pares = []
        for lugar in lugares:
            pid = lugar.get("id")
            if pid is None:
                continue
            imagenes = lugar.get("images")
            if not imagenes:
                una = lugar.get("imagen_url") or lugar.get("image_url")
                imagenes = [una] if una else []
            for u in imagenes:
                pares.append((pid, u))

        print(f"  📋 {len(lugares)} lugares · {len(pares)} imágenes de referencia a indexar.")

        nuevos_vectores = []
        with ThreadPoolExecutor(max_workers=MAX_WORKERS) as pool:
            for resultado in pool.map(procesar_imagen, pares):
                if resultado:
                    nuevos_vectores.append(resultado)

        # Intercambio atómico de la base de datos en memoria (Previene condiciones de carrera)
        vector_database = nuevos_vectores
        ids_unicos = len({v["id"] for v in vector_database})
        print(f"✅ Sincronización exitosa: {len(vector_database)} vectores de {ids_unicos} lugares.\n")
    except Exception as e:
        print(f"❌ Error crítico durante la sincronización del catálogo: {e}")


# ==============================================================================
# ENDPOINTS DE LA API REST
# ==============================================================================
@app.route("/search", methods=["POST"])
def search():
    """
    Endpoint de Búsqueda K-NN (K-Nearest Neighbors).
    Recibe una imagen codificada en Base64 y calcula los 5 mejores matches contra la memoria.
    """
    if not vector_database:
        return jsonify({"success": False, "error": "El índice vectorial está vacío. Ejecute /refresh."}), 503

    try:
        data = request.get_json(force=True)
        if not data or "image" not in data:
            return jsonify({"success": False, "error": "Parámetro obligatorio 'image' ausente."}), 400

        image_b64 = data["image"]
        # Sanitización de Data-URLs enviadas habitualmente por navegadores web
        if "," in image_b64:
            image_b64 = image_b64.split(",", 1)[1]

        # Decodificación y reconstrucción de la imagen en memoria binaria
        image_bytes = base64.b64decode(image_b64)
        image       = Image.open(io.BytesIO(image_bytes)).convert("RGB")

        # Generación del vector de la consulta (Query Vector)
        query_vec = get_embedding(image)

        # Similitud coseno (producto punto, vectores normalizados) contra TODAS las
        # imágenes de referencia. Como un lugar puede tener varias fotos, nos quedamos
        # con el MEJOR puntaje por lugar (best-match-per-place) y deduplicamos.
        mejores = {}
        for item in vector_database:
            score = float(np.dot(query_vec, item["vector"]))
            pid = item["id"]
            if pid not in mejores or score > mejores[pid]:
                mejores[pid] = score

        matches = [{"id": pid, "score": sc} for pid, sc in mejores.items()]
        matches.sort(key=lambda x: x["score"], reverse=True)

        return jsonify({"success": True, "matches": matches[:5]})

    except Exception as e:
        print(f"❌ Error interno en procesamiento de búsqueda: {e}")
        return jsonify({"success": False, "error": str(e)}), 500


@app.route("/refresh", methods=["POST"])
def refresh():
    """Endpoint manual para invalidar la caché e iniciar re-indexación."""
    construir_cache()
    return jsonify({"success": True, "vectores_indexados": len(vector_database)})


@app.route("/health", methods=["GET"])
def health():
    """Endpoint de control de estado (Liveness/Readiness Probe)."""
    return jsonify({
        "status": "online",
        "device": device,
        "vectores_en_memoria": len(vector_database),
    })


if __name__ == "__main__":
    # Construye el índice de vectores al arrancar el proceso
    construir_cache()
    # Ejecución del servidor local en modo producción (debug=False)
    app.run(host="127.0.0.1", port=5001, debug=False)