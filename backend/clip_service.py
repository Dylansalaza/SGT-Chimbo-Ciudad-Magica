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
import sys
import json
import base64
import ipaddress
import socket
from functools import wraps
from urllib.parse import urlparse
from concurrent.futures import ThreadPoolExecutor

# En muchos hostings/consolas la codificación por defecto NO es UTF-8 y los
# emojis de los mensajes rompían el arranque con UnicodeEncodeError. Forzamos
# UTF-8 en la salida para que el servicio arranque en cualquier entorno.
for _stream in (sys.stdout, sys.stderr):
    try:
        _stream.reconfigure(encoding="utf-8")
    except Exception:
        pass

import numpy as np
import requests
import onnxruntime as ort
from flask import Flask, jsonify, request
from PIL import Image

# ==============================================================================
# CONFIGURACIÓN (configurable por variables de entorno para producción)
# ==============================================================================
LARAVEL_URL = os.getenv("LARAVEL_URL", "http://127.0.0.1:3000")
HTTP_TIMEOUT = int(os.getenv("CLIP_HTTP_TIMEOUT", "30"))
MAX_WORKERS  = int(os.getenv("CLIP_INDEX_WORKERS", "10"))
# ⚠️ Por defecto escucha SOLO en 127.0.0.1: el servicio es interno (lo consumen
# el worker y Laravel en la misma máquina). NUNCA lo expongas a 0.0.0.0 sin
# definir además CLIP_AUTH_TOKEN (abajo), o cualquiera podría lanzar /refresh
# (agota CPU/red re-indexando) o /search contra el servidor.
HOST         = os.getenv("CLIP_HOST", "127.0.0.1")
PORT         = int(os.getenv("CLIP_PORT", "5001"))
# Token compartido opcional. Si se define, /search y /refresh exigen la cabecera
# X-CLIP-Token. Si queda vacío (desarrollo local), no se exige nada.
AUTH_TOKEN   = os.getenv("CLIP_AUTH_TOKEN", "").strip()
# Tope de bytes al descargar imágenes del catálogo (evita agotar memoria con una
# imagen gigante o una URL maliciosa que devuelva GBs).
MAX_IMAGE_BYTES = int(os.getenv("CLIP_MAX_IMAGE_BYTES", str(16 * 1024 * 1024)))  # 16 MB
MODEL_PATH   = os.getenv(
    "CLIP_ONNX_PATH",
    os.path.join(os.path.dirname(os.path.abspath(__file__)), "models", "clip_image_int8.onnx"),
)

app = Flask(__name__)
# Rechaza cuerpos de petición gigantes (una imagen de 8 MB en Base64 ≈ 11 MB;
# damos margen a 20 MB). Protege /search de un payload que reviente la RAM.
app.config["MAX_CONTENT_LENGTH"] = int(os.getenv("CLIP_MAX_CONTENT_LENGTH", str(20 * 1024 * 1024)))

# Blindaje anti "decompression bomb": una imagen pequeña que se descomprime a
# dimensiones enormes agotaría la memoria. PIL lanza error por encima de este tope.
Image.MAX_IMAGE_PIXELS = int(os.getenv("CLIP_MAX_IMAGE_PIXELS", str(40_000_000)))  # ~40 MP


def _requiere_token(fn):
    """Exige la cabecera X-CLIP-Token solo si CLIP_AUTH_TOKEN está configurado."""
    @wraps(fn)
    def wrapper(*args, **kwargs):
        if AUTH_TOKEN:
            enviado = request.headers.get("X-CLIP-Token", "")
            # Comparación en tiempo constante (evita timing attacks sobre el token).
            import hmac
            if not hmac.compare_digest(enviado, AUTH_TOKEN):
                return jsonify({"success": False, "error": "No autorizado."}), 401
        return fn(*args, **kwargs)
    return wrapper


def _url_es_segura(url: str) -> bool:
    """
    Defensa anti-SSRF: solo http/https y bloquea el endpoint de metadatos de la
    nube (169.254.169.254) y direcciones link-local. NO bloquea IPs privadas /
    localhost porque las imágenes legítimas del catálogo se sirven desde el
    propio Laravel (que suele vivir en la misma máquina / red interna).
    """
    try:
        p = urlparse(url)
    except Exception:
        return False
    if p.scheme not in ("http", "https") or not p.hostname:
        return False
    try:
        # Resuelve el host a IPs y bloquea link-local (metadata cloud) y multicast/reservadas.
        for info in socket.getaddrinfo(p.hostname, None):
            ip = ipaddress.ip_address(info[4][0])
            if ip.is_link_local or ip.is_multicast or ip.is_reserved or ip.is_unspecified:
                return False
    except Exception:
        # Si no resuelve, dejamos que requests falle luego con su propio timeout.
        return True
    return True

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
def _descargar_imagen(url: str) -> bytes | None:
    """
    Descarga una imagen de forma defensiva:
      · Solo http/https y hosts no link-local (anti-SSRF, ver _url_es_segura).
      · Sin seguir redirecciones (evita el SSRF por redirect a un servicio interno).
      · Con tope de tamaño en streaming (evita agotar la memoria).
    """
    if not _url_es_segura(url):
        print(f"  ⚠️ URL de imagen rechazada por política de seguridad: {url}")
        return None

    with requests.get(url, timeout=HTTP_TIMEOUT, stream=True, allow_redirects=False) as resp:
        if resp.status_code != 200:
            return None
        # Corta temprano si el servidor anuncia un tamaño mayor al permitido.
        largo = resp.headers.get("Content-Length")
        if largo and int(largo) > MAX_IMAGE_BYTES:
            print(f"  ⚠️ Imagen demasiado grande ({largo} bytes) rechazada: {url}")
            return None

        buffer = io.BytesIO()
        for chunk in resp.iter_content(chunk_size=65536):
            buffer.write(chunk)
            if buffer.tell() > MAX_IMAGE_BYTES:
                print(f"  ⚠️ Imagen excede {MAX_IMAGE_BYTES} bytes durante la descarga: {url}")
                return None
        return buffer.getvalue()


def procesar_imagen(par: tuple) -> dict | None:
    """Descarga UNA imagen de referencia (place_id, url) y calcula su embedding."""
    place_id, url = par
    if not url:
        return None
    if not url.startswith("http"):
        url = LARAVEL_URL + ("" if url.startswith("/") else "/") + url
    try:
        contenido = _descargar_imagen(url)
        if contenido:
            img = Image.open(io.BytesIO(contenido)).convert("RGB")
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
@_requiere_token
def search():
    """Búsqueda K-NN: recibe imagen Base64 y devuelve los 5 mejores lugares."""
    if not vector_database:
        return jsonify({"success": False, "error": "El índice vectorial está vacío. Ejecute /refresh."}), 503
    try:
        data = request.get_json(force=True, silent=True)
        if not data or "image" not in data:
            return jsonify({"success": False, "error": "Parámetro obligatorio 'image' ausente."}), 400

        image_b64 = data["image"]
        if not isinstance(image_b64, str):
            return jsonify({"success": False, "error": "El parámetro 'image' debe ser texto Base64."}), 400
        if "," in image_b64:
            image_b64 = image_b64.split(",", 1)[1]
        # Tope de tamaño del Base64 (~22 MB de texto ≈ 16 MB de imagen). Evita
        # decodificar en memoria un payload gigante aunque pase el MAX_CONTENT_LENGTH.
        if len(image_b64) > MAX_IMAGE_BYTES * 2:
            return jsonify({"success": False, "error": "La imagen es demasiado grande."}), 413

        crudo = base64.b64decode(image_b64, validate=False)
        image = Image.open(io.BytesIO(crudo)).convert("RGB")
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
        # Log detallado en el servidor; al cliente solo un mensaje genérico
        # (el detalle podría filtrar rutas/internos hasta la interfaz del usuario).
        print(f"❌ Error interno en procesamiento de búsqueda: {e}")
        return jsonify({"success": False, "error": "No se pudo procesar la imagen enviada."}), 500


@app.route("/refresh", methods=["POST"])
@_requiere_token
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
