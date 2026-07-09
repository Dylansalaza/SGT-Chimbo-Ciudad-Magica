"""
Servidor API Flask — Motor de Búsqueda Visual con CLIP (runtime ONNX int8).

Versión de PRODUCCIÓN LIGERA: usa `onnxruntime` en lugar de PyTorch, por lo que
NO carga el framework completo en memoria (~400 MB de RAM en vez de ~2 GB) y no
requiere `torch`/`transformers` en el servidor. El modelo se genera una sola vez
con `scripts/export_clip_onnx.py` y se coloca en `models/clip_image_int8.onnx`.

El contrato HTTP es idéntico al de la versión anterior (endpoints /search,
/refresh, /health), así que `worker.py` NO necesita ningún cambio.
"""

import io
import os
import json
import base64
from concurrent.futures import ThreadPoolExecutor

import numpy as np
import requests
import onnxruntime as ort
from flask import Flask, jsonify, request
from flask_cors import CORS
from PIL import Image

# ==============================================================================
# CONFIGURACIÓN (configurable por variables de entorno para producción)
# ==============================================================================
LARAVEL_URL = os.getenv("LARAVEL_URL", "http://127.0.0.1:3000")
HTTP_TIMEOUT = int(os.getenv("CLIP_HTTP_TIMEOUT", "30"))
MAX_WORKERS  = int(os.getenv("CLIP_INDEX_WORKERS", "10"))
HOST         = os.getenv("CLIP_HOST", "127.0.0.1")
PORT         = int(os.getenv("CLIP_PORT", "5001"))
MODEL_PATH   = os.getenv(
    "CLIP_ONNX_PATH",
    os.path.join(os.path.dirname(os.path.abspath(__file__)), "models", "clip_image_int8.onnx"),
)

app = Flask(__name__)
CORS(app)

# ==============================================================================
# INICIALIZACIÓN DEL MODELO ONNX
# ==============================================================================
if not os.path.exists(MODEL_PATH):
    raise SystemExit(
        f"❌ No se encontró el modelo ONNX en {MODEL_PATH}.\n"
        f"   Genéralo con:  python scripts/export_clip_onnx.py  y sube el archivo al servidor."
    )

print(f"⏳ Cargando modelo CLIP (ONNX int8) desde {MODEL_PATH} …")
# Limitar hilos mantiene el uso de CPU/RAM bajo control en servidores pequeños.
_so = ort.SessionOptions()
_so.intra_op_num_threads = int(os.getenv("CLIP_ORT_THREADS", "2"))
session   = ort.InferenceSession(MODEL_PATH, sess_options=_so, providers=["CPUExecutionProvider"])
IN_NAME   = session.get_inputs()[0].name
print("✅ Modelo ONNX inicializado.")

# Constantes de preprocesamiento de CLIP (idénticas a CLIPImageProcessor).
CLIP_MEAN = np.array([0.48145466, 0.4578275, 0.40821073], dtype=np.float32)
CLIP_STD  = np.array([0.26862954, 0.26130258, 0.27577711], dtype=np.float32)
TARGET    = 224

# Base de vectores indexada en memoria (RAM-cache): [{"id": int, "vector": np.ndarray}]
vector_database = []


# ==============================================================================
# PREPROCESAMIENTO + EMBEDDING
# ==============================================================================
def _preprocess(image: Image.Image) -> np.ndarray:
    """Resize (borde corto→224, bicúbico) + center-crop 224 + normalización CLIP → NCHW."""
    image = image.convert("RGB")
    w, h = image.size
    scale = TARGET / min(w, h)
    new_w, new_h = round(w * scale), round(h * scale)
    image = image.resize((new_w, new_h), Image.BICUBIC)
    left = (new_w - TARGET) // 2
    top  = (new_h - TARGET) // 2
    image = image.crop((left, top, left + TARGET, top + TARGET))
    arr = np.asarray(image, dtype=np.float32) / 255.0
    arr = (arr - CLIP_MEAN) / CLIP_STD
    arr = arr.transpose(2, 0, 1)
    return arr[np.newaxis, ...].astype(np.float32)


def get_embedding(image: Image.Image) -> np.ndarray:
    """Vector de características L2-normalizado de una imagen (para similitud coseno)."""
    px = _preprocess(image)
    out = session.run(None, {IN_NAME: px})[0][0]          # (512,)
    return (out / (np.linalg.norm(out) + 1e-12)).astype(np.float32)


# ==============================================================================
# INDEXACIÓN DEL CATÁLOGO (idéntica a la versión anterior)
# ==============================================================================
def procesar_imagen(par: tuple) -> dict | None:
    """Descarga UNA imagen de referencia (place_id, url) y calcula su embedding."""
    place_id, url = par
    if not url:
        return None
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
    """Sincroniza y re-indexa el catálogo completo de vectores desde Laravel."""
    global vector_database
    url = f"{LARAVEL_URL}/api/clip-catalog"
    print(f"🔄 Sincronizando catálogo desde {url} ...")
    try:
        try:
            resp = requests.get(url, timeout=HTTP_TIMEOUT)
        except requests.exceptions.RequestException as conn_err:
            print(f"❌ No se pudo conectar con Laravel ({LARAVEL_URL}). Detalle: {conn_err}")
            return

        if resp.status_code != 200:
            print(f"❌ Sincronización abortada. Laravel devolvió HTTP {resp.status_code}.")
            print(f"   Cuerpo (primeros 200): {resp.text[:200]!r}")
            return

        cuerpo = resp.content.decode("utf-8-sig").strip()
        if not cuerpo:
            print("❌ Laravel respondió 200 pero con cuerpo VACÍO.")
            return

        try:
            lugares = json.loads(cuerpo)
        except json.JSONDecodeError:
            print("❌ La respuesta NO es JSON válido (¿página de error de Laravel?).")
            print(f"   Abre en el navegador: {url}")
            return

        if isinstance(lugares, dict):
            lugares = lugares.get("data", [])

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

        vector_database = nuevos_vectores
        ids_unicos = len({v["id"] for v in vector_database})
        print(f"✅ Sincronización exitosa: {len(vector_database)} vectores de {ids_unicos} lugares.\n")
    except Exception as e:
        print(f"❌ Error crítico durante la sincronización del catálogo: {e}")


# ==============================================================================
# ENDPOINTS DE LA API REST (contrato idéntico al anterior)
# ==============================================================================
@app.route("/search", methods=["POST"])
def search():
    """Búsqueda K-NN: recibe imagen Base64 y devuelve los 5 mejores lugares."""
    if not vector_database:
        return jsonify({"success": False, "error": "El índice vectorial está vacío. Ejecute /refresh."}), 503
    try:
        data = request.get_json(force=True)
        if not data or "image" not in data:
            return jsonify({"success": False, "error": "Parámetro obligatorio 'image' ausente."}), 400

        image_b64 = data["image"]
        if "," in image_b64:
            image_b64 = image_b64.split(",", 1)[1]

        image = Image.open(io.BytesIO(base64.b64decode(image_b64))).convert("RGB")
        query_vec = get_embedding(image)

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
    """Invalida la caché e inicia re-indexación del catálogo."""
    construir_cache()
    return jsonify({"success": True, "vectores_indexados": len(vector_database)})


@app.route("/health", methods=["GET"])
def health():
    """Control de estado (liveness/readiness)."""
    return jsonify({"status": "online", "runtime": "onnxruntime-int8",
                    "vectores_en_memoria": len(vector_database)})


if __name__ == "__main__":
    construir_cache()
    app.run(host=HOST, port=PORT, debug=False)
