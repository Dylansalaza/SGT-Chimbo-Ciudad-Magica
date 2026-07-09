"""
Worker Asíncrono de Base de Datos - Orquestador CLIP.
Utiliza PostgreSQL de forma directa como Broker de Mensajería implementando
las cláusulas atómicas FOR UPDATE SKIP LOCKED (Manejo de Concurrencia Seguro).
"""

import os
import json
import time
import psycopg2
from psycopg2.extras import RealDictCursor
import requests

# ==============================================================================
# CONFIGURACIÓN DE INFRAESTRUCTURA
# ==============================================================================
def _cargar_env(ruta: str) -> None:
    """
    Carga las variables del .env de Laravel en os.environ (sin dependencias
    externas). Así las credenciales viven en un único sitio y NO quedan
    incrustadas en el código fuente (buena práctica de seguridad).
    """
    if not os.path.exists(ruta):
        return
    with open(ruta, encoding="utf-8") as fh:
        for linea in fh:
            linea = linea.strip()
            if not linea or linea.startswith("#") or "=" not in linea:
                continue
            clave, valor = linea.split("=", 1)
            valor = valor.strip().strip('"').strip("'")
            os.environ.setdefault(clave.strip(), valor)


_cargar_env(os.path.join(os.path.dirname(os.path.abspath(__file__)), ".env"))

# Credenciales tomadas del entorno; el password ya NO está hardcodeado.
DB_CONFIG = {
    "host":     os.getenv("DB_HOST", "127.0.0.1"),
    "port":     os.getenv("DB_PORT", "5432"),
    "user":     os.getenv("DB_USERNAME", "postgres"),
    "password": os.getenv("DB_PASSWORD", ""),
    "database": os.getenv("DB_DATABASE", "turismo"),
}

FLASK_URL    = os.getenv("CLIP_SERVICE_URL", "http://127.0.0.1:5001")
HTTP_TIMEOUT = int(os.getenv("CLIP_WORKER_TIMEOUT", "45"))

# Carpeta física del storage público de Laravel (misma máquina que este worker).
# Nos permite leer la imagen directo del disco en vez de pedirla por HTTP al
# propio servidor de Laravel, ahorrando un viaje de ida y vuelta completo.
STORAGE_PUBLIC_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "storage", "app", "public")

# Umbral mínimo de similitud (coseno 0-1) para aceptar una coincidencia.
# Por debajo de esto se considera que NO se reconoció el lugar.
# Súbelo para ser más estricto (menos falsos positivos), bájalo para ser más permisivo.
MATCH_THRESHOLD = float(os.getenv("CLIP_MATCH_THRESHOLD", "0.75"))


def conectar_db():
    """Establece una conexión resiliente con PostgreSQL con re-intentos infinitos."""
    while True:
        try:
            conn = psycopg2.connect(**DB_CONFIG)
            print("✅ Conexión establecida de forma exitosa con PostgreSQL.")
            return conn
        except Exception as e:
            print(f"⏳ Base de datos inaccesible. Reintentando en 2 segundos... Error: ({e})")
            time.sleep(2)


def _leer_imagen_local(image_url: str) -> bytes | None:
    """
    Si la URL apunta al storage público de ESTE Laravel (misma máquina que el
    worker), lee el archivo directo del disco en vez de pedirlo por HTTP.
    Evita un round-trip completo de red contra el servidor de desarrollo PHP,
    que suele ser el cuello de botella real cuando "se demora mucho".
    """
    marcador = "/storage/"
    idx = image_url.find(marcador)
    if idx == -1:
        return None
    ruta_relativa = image_url[idx + len(marcador):].split("?", 1)[0]
    ruta_local = os.path.normpath(os.path.join(STORAGE_PUBLIC_DIR, ruta_relativa))
    if os.path.isfile(ruta_local):
        with open(ruta_local, "rb") as fh:
            return fh.read()
    return None


def consultar_clip(image_url: str) -> list:
    """
    Obtiene la imagen objetivo (directo del disco si es posible, o por HTTP si
    el storage vive en otra máquina), la convierte a Base64 y consulta al
    Motor Flask.
    """
    contenido = _leer_imagen_local(image_url)
    if contenido is None:
        # Fallback: descarga por HTTP (p. ej. storage remoto o de otra máquina)
        resp = requests.get(image_url, timeout=HTTP_TIMEOUT)
        resp.raise_for_status()
        contenido = resp.content

    # Codificación a texto Base64 para el contrato de la API de Flask
    import base64
    image_b64 = base64.b64encode(contenido).decode("utf-8")

    flask_resp = requests.post(
        f"{FLASK_URL}/search",
        json={"image": image_b64},
        timeout=HTTP_TIMEOUT,
    )
    flask_resp.raise_for_status()
    resultado = flask_resp.json()

    if not resultado.get("success"):
        raise RuntimeError(f"Error devuelto por el nodo Flask: {resultado.get('error')}")

    matches = resultado.get("matches", [])
    if not matches:
        raise RuntimeError("El motor CLIP no retornó vectores de coincidencia válidos.")

    return matches


def main():
    db = conectar_db()
    print("🧠 Worker Inteligente CLIP Iniciado. Escuchando tabla 'image_searches'...\n")

    while True:
        # Reiniciamos en cada ciclo para no arrastrar el id de un ticket anterior
        # al manejador de errores (antes podía marcar como 'failed' un ticket ajeno).
        search_id = None
        try:
            # Verificación preventiva del estado de la conexión antes de iniciar transacción
            if db.closed:
                db = conectar_db()

            with db.cursor(cursor_factory=RealDictCursor) as cur:
                # --------------------------------------------------------------
                # CONCURRENCIA ATÓMICA (Patrón Productor/Consumidor sobre cola)
                # --------------------------------------------------------------
                # FOR UPDATE: bloquea la fila para que ningún otro proceso la edite.
                # SKIP LOCKED: si otro worker ya procesa una fila, la ignora y salta.
                # Permite escalar a N workers idénticos sin colisiones de datos.
                cur.execute("""
                    SELECT id, image_path
                    FROM image_searches
                    WHERE status = 'pending'
                    ORDER BY id ASC
                    LIMIT 1
                    FOR UPDATE SKIP LOCKED;
                """)
                ticket = cur.fetchone()

                # Sin tickets pendientes: liberamos CPU y esperamos el siguiente sondeo.
                if not ticket:
                    db.commit()  # libera la transacción/candado abierto por el SELECT FOR UPDATE
                    time.sleep(1)
                    continue

                search_id = ticket["id"]
                image_path = ticket["image_path"]

                print(f"\n📸 [TICKET {search_id}] Cambiando estado a: 'processing'")

                # Transición inmediata a 'processing' para notificar al cliente (polling).
                cur.execute(
                    "UPDATE image_searches SET status = 'processing' WHERE id = %s",
                    (search_id,),
                )
                db.commit()  # confirma y libera el candado temporal de la fila

                if not image_path:
                    raise ValueError(f"El ticket {search_id} no tiene una URL de imagen.")

                # Consulta al modelo de visión artificial.
                matches = consultar_clip(image_path)
                mejor_match = matches[0]
                lugar_id = int(mejor_match["id"])
                mejor_score = float(mejor_match["score"])

                print(f"  🎯 Mejor candidato -> ID={lugar_id} | Score={mejor_score:.4f} (umbral={MATCH_THRESHOLD})")

                candidatos_json = json.dumps(matches[:5])

                # --------------------------------------------------------------
                # UMBRAL DE CONFIANZA
                # CLIP SIEMPRE devuelve el lugar más parecido, aunque la imagen no
                # tenga nada que ver (un león vs un parque). Si la similitud está
                # por debajo del umbral, NO afirmamos un resultado: lo marcamos
                # como "completado sin coincidencia" (tourist_place_id = NULL).
                # --------------------------------------------------------------
                if mejor_score < MATCH_THRESHOLD:
                    print(f"  ⚠️ Score {mejor_score:.4f} < umbral {MATCH_THRESHOLD}: sin coincidencia confiable.")
                    cur.execute(
                        """UPDATE image_searches
                              SET status           = 'completed',
                                  tourist_place_id = NULL,
                                  top_score        = %s,
                                  candidates       = %s,
                                  error_message    = %s
                            WHERE id = %s""",
                        (mejor_score, candidatos_json,
                         'No se reconoció ningún lugar con suficiente confianza. Prueba con una foto más parecida al sitio.',
                         search_id),
                    )
                    db.commit()
                    print(f"  ✅ Ticket {search_id} cerrado SIN coincidencia.")
                else:
                    cur.execute(
                        """UPDATE image_searches
                              SET status           = 'completed',
                                  tourist_place_id = %s,
                                  top_score        = %s,
                                  candidates       = %s,
                                  error_message    = NULL
                            WHERE id = %s""",
                        (lugar_id, mejor_score, candidatos_json, search_id),
                    )
                    db.commit()
                    print(f"  ✅ Ticket {search_id} finalizado: lugar {lugar_id} ({mejor_score:.2%}).")

        except Exception as exc:
            print(f"❌ Error crítico procesando Ticket: {exc}")
            try:
                db.rollback()  # revierte la transacción fallida actual
                if search_id is not None:
                    # Registramos el error en la propia tabla para auditoría.
                    with db.cursor() as err_cur:
                        err_cur.execute(
                            """UPDATE image_searches
                               SET status = 'failed', error_message = %s
                               WHERE id = %s""",
                            (str(exc), search_id),
                        )
                    db.commit()
                    print(f"  ⚠️ Ticket {search_id} marcado como 'failed' de forma segura.")
            except Exception as db_exc:
                print(f"❌ Imposible actualizar estado de falla: {db_exc}")

            time.sleep(1)


if __name__ == "__main__":
    main()
