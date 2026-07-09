# Sistema de Gestión Turística — San José de Chimbo

Portal web turístico del cantón San José de Chimbo (provincia de Bolívar,
Ecuador). Incluye un mapa interactivo, un asistente virtual, búsqueda de
atractivos **por imagen con inteligencia artificial** y un panel de
administración de contenidos.

> Proyecto de tesis. El código del sistema (backend + frontend) vive en este
> repositorio; la documentación de tesis se mantiene aparte.

---

## ✨ Funcionalidades

- 🗺️ **Mapa interactivo** (Leaflet) con atractivos turísticos, filtros por
  categoría/precio y "cómo llegar" vía Google Maps.
- 🤖 **Asistente virtual** con árbol de preguntas frecuentes + respuestas por IA
  (Groq / Llama) para consultas en lenguaje natural.
- 🔍 **Búsqueda por imagen (IA):** el usuario sube una foto y el sistema
  identifica el lugar usando el modelo **CLIP** (visión + lenguaje).
- 📰 Gestión de **noticias, eventos y galerías**.
- 🔐 **Panel de administración** para el personal del GAD Municipal.
- ♿ Widget de accesibilidad y lectura en voz alta de las fichas.

---

## 🧱 Arquitectura

| Capa | Tecnología |
|------|-----------|
| Frontend | React 18 + Vite + Tailwind CSS |
| Backend | Laravel 12 (PHP 8.2) — API REST + panel Blade |
| Base de datos | PostgreSQL |
| Búsqueda por imagen | Python · Flask · PyTorch · HuggingFace **CLIP** |
| Cola de trabajos | Driver `database` de Laravel (PostgreSQL como broker) |
| Chat IA | API de Groq (modelo Llama) |

El flujo de búsqueda por imagen usa PostgreSQL como cola: Laravel crea un
"ticket", `worker.py` lo toma de forma concurrente-segura
(`FOR UPDATE SKIP LOCKED`), pide el *embedding* a `clip_service.py` (Flask) y
guarda el resultado.

---

## 🚀 Puesta en marcha (desarrollo local)

### Requisitos
- PHP 8.2+ y [Composer](https://getcomposer.org)
- Node.js 20+
- PostgreSQL 14+
- Python 3.10+

### 1. Backend (Laravel)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Edita .env: DB_CONNECTION=pgsql, credenciales de PostgreSQL y GROQ_API_KEY
php artisan migrate --seed
php artisan storage:link
php artisan serve --port=3000
```

### 2. Servicio de IA (Python — CLIP en ONNX)
El motor de visión usa el modelo CLIP como **ONNX cuantizado (int8)**, así corre
con ~400 MB de RAM y sin PyTorch. Se genera una sola vez:
```bash
cd backend
# (a) Generar el modelo ONNX — una única vez (requiere PyTorch, solo aquí)
python -m venv .venv-export
# Windows: .venv-export\Scripts\activate   |   Linux/Mac: source .venv-export/bin/activate
pip install -r requirements-export.txt
python scripts/export_clip_onnx.py         # crea models/clip_image_int8.onnx y verifica paridad

# (b) Ejecutar los servicios (runtime ligero, sin PyTorch)
python -m venv .venv
# activar el entorno…
pip install -r requirements.txt
python clip_service.py    # Flask + onnxruntime (puerto 5001)
python worker.py          # en otra terminal: procesa las búsquedas
```
> El paso (a) descarga el modelo CLIP (~600 MB) desde HuggingFace solo esa vez.

### 3. Frontend (React)
```bash
cd frontend
npm install
# Opcional: crea frontend/.env con  VITE_API_URL=http://127.0.0.1:3000/api
npm run dev               # http://localhost:5173
```

---

## 🔑 Variables de entorno clave (`backend/.env`)

| Variable | Descripción |
|----------|-------------|
| `DB_CONNECTION` | `pgsql` |
| `DB_*` | Conexión a PostgreSQL |
| `GROQ_API_KEY` | Clave de [Groq](https://console.groq.com/keys) para el chat IA |
| `GROQ_MODEL` | Modelo de chat (p. ej. `llama-3.1-8b-instant`) |
| `CLIP_SERVICE_URL` | URL del servicio Flask de CLIP (por defecto `http://127.0.0.1:5001`) |
| `CLIP_MATCH_THRESHOLD` | Umbral de similitud (0–1) para aceptar una coincidencia |

⚠️ **Nunca** subas tu archivo `.env` real al repositorio: contiene credenciales.
Solo se versiona `.env.example`.

---

## ☁️ Despliegue en producción

Para publicar el sistema en internet (URL pública), consulta
**[DEPLOY.md](DEPLOY.md)** — guía paso a paso adaptada a este stack.

---

## 📄 Licencia

Proyecto académico. Uso educativo.
