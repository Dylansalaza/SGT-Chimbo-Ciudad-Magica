# Arquitectura del Sistema — SGT Chimbo (Ciudad Mágica)

Sistema de Gestión Turística del cantón San José de Chimbo. Documento técnico de
arquitectura: describe cómo está construido el sistema, cómo fluyen las
peticiones y cómo encajan sus piezas. Para **desplegar** en producción, ver
[`DEPLOY.md`](DEPLOY.md).

---

## 1. Visión general

El sistema tiene tres piezas que corren como procesos independientes:

| Pieza | Tecnología | Rol |
|-------|-----------|-----|
| **Frontend público** | React 18 + Vite + Tailwind | Sitio turístico que ve el ciudadano/turista (SPA). |
| **Backend / API + Panel** | Laravel 12 (PHP 8.2+) | API REST pública para el frontend **y** panel de administración en Blade. |
| **Motor de IA** | Python (Flask + ONNX Runtime) + worker | Búsqueda de lugares por imagen (CLIP). Corre aparte del backend. |

Base de datos: **PostgreSQL**. La misma BD sirve además como cola de trabajos del
motor de IA (ver §7).

```
   Navegador
      │
      ▼
┌─────────────┐    HTTPS/JSON     ┌──────────────────────────┐
│  React SPA  │ ────────────────► │   Laravel 12 (API + Web) │
│  (Vercel)   │ ◄──────────────── │   - API REST pública     │
└─────────────┘                   │   - Panel admin (Blade)  │
                                  └───────────┬──────────────┘
                                              │ SQL
                                       ┌──────▼───────┐
                                       │  PostgreSQL  │◄─── cola image_searches
                                       └──────┬───────┘
                                              │ FOR UPDATE SKIP LOCKED
                                     ┌────────▼─────────┐   HTTP    ┌──────────────────┐
                                     │   worker.py      │ ────────► │  clip_service.py │
                                     │ (orquestador)    │ ◄──────── │  Flask + ONNX    │
                                     └──────────────────┘  score    └──────────────────┘
```

---

## 2. Estructura del repositorio

```
TESIS/
├── backend/                 # Laravel 12 + servicio Python de IA
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/         # API pública (raíz) + Admin/ (panel Blade) + Auth/
│   │   │   └── Middleware/          # EnsureUserIsAdmin, EnsureRol, TrackVisits…
│   │   ├── Models/                  # Eloquent (Event, News, TouristPlace, …)
│   │   ├── Services/                # FlaskImageSearchService (cliente del motor CLIP)
│   │   ├── Contracts/               # ImageSearchInterface (abstracción del motor)
│   │   └── Support/                 # ImageOptimizer (conversión a WebP)
│   ├── routes/
│   │   ├── api.php                  # Rutas de la API pública (consumidas por React)
│   │   └── web.php                  # Panel admin (Blade) + auth + reset password
│   ├── resources/views/Admin/       # Vistas Blade del panel de administración
│   ├── clip_service.py              # Servicio Flask del motor CLIP (ONNX int8)
│   ├── worker.py                    # Orquestador asíncrono (lee la cola, llama a CLIP)
│   └── scripts/export_clip_onnx.py  # Genera el modelo ONNX (uso único)
└── frontend/                # React 18 + Vite
    └── src/
        ├── api.js                   # Capa axios (baseURL desde VITE_API_URL)
        ├── App.jsx                  # Router + code-splitting por ruta
        ├── pages/                   # Home, Eventos, Noticias, Galerias, ChimboMap, login…
        ├── components/              # Navbar, Footer, Chatbot, accesibilidad, mapa…
        └── hooks/useTouristPlaces.js
```

---

## 3. Backend Laravel

### 3.1 Dos superficies, un mismo backend

- **API REST pública** (`routes/api.php`): la consume el frontend React. Devuelve
  JSON. Prefijo `/api`.
- **Panel de administración** (`routes/web.php`): páginas Blade renderizadas en el
  servidor, bajo el prefijo `/admin`. Es donde los funcionarios gestionan el
  contenido.

Por eso hay controladores "duplicados" a propósito: `EventController` (API, JSON)
vs `Admin\EventoController` (panel, Blade). No es repetición accidental: son dos
interfaces distintas sobre el mismo modelo.

### 3.2 Middleware y roles

Registrados en `bootstrap/app.php`:

| Alias | Clase | Qué hace |
|-------|-------|----------|
| `admin` | `EnsureUserIsAdmin` | Exige usuario autenticado **y** con permisos de administrador. |
| `rol`   | `EnsureRol` | Exige un rol concreto: `rol:administrador` o `rol:admin_turismo`. |
| (web)   | `TrackVisits` | Registra visitas anónimas para la analítica. |

**Dos roles de administrador:**

- `administrador` — gestiona **usuarios** (crear cuentas, asignar roles, dar de baja).
- `admin_turismo` — gestiona **contenido** turístico (eventos, noticias, lugares,
  galerías, home, reportes).

En `routes/web.php` cada sección está protegida por su rol específico. En
`routes/api.php` las rutas de escritura usan `auth:sanctum` + `admin`.

### 3.3 Autenticación (Laravel Sanctum)

Un único endpoint `POST /login` (`AuthController`) sirve dos flujos:

- **Web** (formulario Blade): inicia sesión de servidor y redirige al dashboard.
- **SPA** (React): emite un **token Sanctum** (`access_token` tipo Bearer) que el
  frontend guarda y envía en cada petición protegida.

No existe registro público de usuarios a propósito: las cuentas se crean solo
desde el panel admin (`Admin\UsuarioController`), donde se asigna el rol. Un alta
sin rol sería un riesgo de escalada de privilegios.

Recuperación de contraseña por correo: `Auth\PasswordResetController`
(token de un solo uso, `throttle:6,1`, el token nunca se muestra en pantalla).

---

## 4. Modelo de datos (tablas principales)

| Tabla | Modelo | Notas |
|-------|--------|-------|
| `users` | `User` | Incluye `is_admin`, `rol`, `recovery_email`. `password`/`remember_token` ocultos en JSON. |
| `admin_roles` | `AdminRole` | Catálogo de roles. |
| `tourist_places` | `TouristPlace` | Lugares del mapa. Campo `activo` = baja lógica (no se borra el registro). |
| `place_categories` | `PlaceCategory` | Categorías de lugares. |
| `reference_images` | `ReferenceImage` | Imágenes de referencia por lugar, usadas por el motor CLIP. |
| `events` / `news` / `galleries` | `Event` / `News` / `Gallery` | Contenido turístico. |
| `home_settings` | `HomeSetting` | Contenido editable del Home (carrusel, bienvenida, destacados). |
| `chat_faqs` | `ChatFaq` | Palabras clave del asistente virtual. |
| `visits` | `Visit` | Analítica de visitas anónimas. |
| `image_searches` | `ImageSearch` | **Cola** de búsquedas por imagen (ver §7). |

Baja lógica: `TouristPlace.activo` permite "dar de baja / reactivar" un lugar sin
perder su información ni su historial (el `Admin\LugarController::destroy` alterna
el campo en vez de borrar).

---

## 5. Frontend React

- **Enrutado y code-splitting** (`App.jsx`): cada página se carga con `React.lazy`,
  así el bundle inicial del Home no arrastra Leaflet (mapa), Swiper ni
  react-dropzone. Rutas: `/`, `/mapa`, `/eventos`, `/eventos/:id`, `/galerias`,
  `/noticias`, `/noticias/:id`, `/login`.
- **Capa de API** (`api.js`): instancia axios con `baseURL = VITE_API_URL`. Las
  páginas que hablan directo con Laravel derivan la URL del backend de esa misma
  variable (`VITE_API_URL` sin el sufijo `/api`) — **no hay URLs hardcodeadas**.
- **Sesión**: el token Sanctum y el usuario se guardan en `localStorage`
  (`token`, `user`); el `Navbar` los lee para mostrar el acceso al panel admin.
- **Accesibilidad**: en el `Navbar` viven el panel de tamaño de texto
  (`AccessibilityWidget`, ♿) y el botón de alto contraste (`HighContrastToggle`).
  El alto contraste también se activa automáticamente en modo oscuro.
- **Rendimiento**: imágenes en WebP, fuentes auto-hospedadas y precargadas,
  preconnect al backend inyectado desde `index.html`, y `/home` cacheado.

### 5.1 Menú "Turismo" del header (`Navbar.jsx`)

El header incluye un menú desplegable **"Turismo"** con las 4 preguntas más
frecuentes del turista. Cada opción navega a una vista y, cuando aplica, envía
un **estado de navegación** (`<Link state={…}>`) que la página del mapa
(`ChimboMap.jsx`) interpreta para filtrar los marcadores:

| Opción | Destino | Estado enviado | Resultado |
|--------|---------|----------------|-----------|
| **¿Qué hacer?** | `/eventos` | — | Agenda de eventos del cantón |
| **¿Cómo llegar?** | `/mapa` | `{ showAll: true }` | **Todos** los lugares atractivos |
| **¿Qué comer?** | `/mapa` | `{ categoriaKeys: ['restaurante','cafeter'] }` | Restaurantes y cafeterías |
| **¿Dónde dormir?** | `/mapa` | `{ categoriaKeys: ['hotel','hostal','hoster'] }` | Hoteles, hostales y hosterías |

- Las `categoriaKeys` son **subcadenas sin tildes**: el mapa hace `includes()`
  sobre la categoría en minúsculas, así `hoster` calza con "Hostería" y
  `cafeter` con "Cafetería" sin depender del acento.
- El desplegable se muestra en **escritorio** (abre con hover/foco) y como una
  **sección plegada dentro del menú hamburguesa** en móvil. Ambos reutilizan la
  misma lista `TURISMO_MENU`.
- En `ChimboMap.jsx`, el `useEffect` que lee `location.state` maneja `showAll`
  (activa "mostrar todos los lugares") y `categoriaKeys` (resalta solo el grupo
  de esa categoría), reiniciando el estado previo para que cada opción muestre
  exactamente lo pedido. Es el mismo mecanismo que ya usa el chatbot para
  centrar y resaltar lugares.
- Las categorías de lugares (Hotel, Hostal, Hostería, Restaurante, Cafetería,
  etc.) se administran desde el panel (`place_categories`); el
  `TouristPlaceSeeder` incluye ejemplos de cada una para que estas opciones
  muestren resultados desde una instalación limpia.

---

## 6. Asistente virtual (chatbot)

`ChatbotController` expone:

- `GET /chat-faqs` — palabras clave/respuestas configuradas por el admin.
- `POST /chat-ai` — respuesta con IA generativa (limitado a 15 req/min por IP para
  no agotar la cuota gratuita).
- `POST /registro-visita` — registra una visita anónima (limitado a 30 req/min).

---

## 7. Pipeline de búsqueda por imagen (IA) — pieza central

Permite que el turista suba una **foto** y el sistema reconozca **qué lugar** de
Chimbo es. El flujo es **asíncrono** para no bloquear la petición web mientras el
modelo procesa la imagen.

```
1. React sube la foto        POST /api/image-search
        │                    (throttle 20/min, valida imagen ≤8 MB)
        ▼
2. Laravel crea un "ticket"  fila en image_searches (status = pending)
   y responde con su id      → React empieza a hacer polling
        │
        ▼
3. worker.py toma el ticket  SELECT … FOR UPDATE SKIP LOCKED
   (usa PostgreSQL como cola: concurrencia segura sin broker externo)
        │
        ▼
4. worker → clip_service     POST /search (imagen)
        │                    Flask calcula el embedding CLIP con ONNX Runtime
        ▼                    y lo compara contra el catálogo de lugares
5. worker guarda el resultado en el ticket (status = completed/failed,
   lugar ganador, score, candidatos similares)
        │
        ▼
6. React (polling)           GET /api/image-search/status/{id}
   recibe el lugar + similares y centra el mapa en él.
```

**Puntos clave de diseño:**

- **La cola es la propia BD.** `worker.py` usa `FOR UPDATE SKIP LOCKED` de
  PostgreSQL para tomar tickets de forma atómica: varios workers podrían correr en
  paralelo sin procesar el mismo ticket dos veces, y sin necesidad de Redis/RabbitMQ.
- **Runtime ligero (ONNX int8).** `clip_service.py` usa `onnxruntime` en vez de
  PyTorch: ~400 MB de RAM en lugar de ~2 GB. El modelo se genera una sola vez con
  `scripts/export_clip_onnx.py` → `models/clip_image_int8.onnx` (no se versiona;
  se sube al servidor).
- **Abstracción (DIP).** Laravel no conoce Flask directamente: `ImageSearchController`
  depende de `ImageSearchInterface`, implementado por `FlaskImageSearchService`.
  Cambiar el motor no toca el controlador.
- **Reindexado.** Al crear/editar/dar de baja un lugar, el `Admin\LugarController`
  llama a `POST /refresh` del servicio CLIP (fire-and-forget, timeout 2 s) para que
  el índice se actualice sin bloquear el guardado.
- **Servicio interno.** `clip_service.py` escucha por defecto **solo en
  `127.0.0.1:5001`**. Si alguna vez se expone, debe definirse `CLIP_AUTH_TOKEN`
  (token compartido) en Laravel y en el servicio.

---

## 8. Seguridad (resumen de lo implementado)

- **Autorización centralizada**: toda ruta que muta datos está detrás de
  `auth:sanctum` + `admin` (API) o del rol correspondiente (panel). Los
  controladores no repiten el chequeo de rol.
- **Rate limiting**: login (5/min por email+IP, 20/min por IP), reset de
  contraseña (6/min), chat IA (15/min), registro de visitas (30/min),
  subida de imágenes (20/min), estadísticas (30/min).
- **Subida de archivos**: validación de mime/tamaño; nombres de archivo generados
  por el servidor (no se confía en el nombre del cliente).
- **Path traversal**: la ruta de respaldo de imágenes (`/storage/{ruta}`) resuelve
  la ruta real y exige que quede dentro de `storage/app/public`.
- **SSRF / lectura de archivos** en el servicio Python: bloquea IPs link-local y de
  metadatos, no sigue redirecciones y limita el tamaño de descarga.
- **Exposición de datos**: `User::$hidden` oculta `password`/`remember_token`;
  los errores de servidor se registran en log pero devuelven un mensaje genérico
  al cliente (no filtran rutas ni clases internas).
- **Mass-assignment**: los controladores usan listas blancas de campos, no
  `$request->all()`.

Antes de publicar, revisar el checklist de despliegue en [`DEPLOY.md`](DEPLOY.md):
`APP_DEBUG=false`, `APP_ENV=production`, CORS restringido al dominio real, SMTP
configurado, `CLIP_AUTH_TOKEN` definido, y rotación de secretos.

---

## 9. Variables de entorno clave

**Backend** (`backend/.env`, plantilla en `.env.production.example`):

| Variable | Uso |
|----------|-----|
| `APP_ENV` / `APP_DEBUG` | En producción: `production` / `false`. |
| `APP_URL` | URL pública del backend. |
| `DB_*` | Conexión PostgreSQL. |
| `FRONTEND_URLS` | Orígenes permitidos por CORS (el dominio del frontend). |
| `CLIP_HOST` / `CLIP_PORT` | Dónde escucha el servicio CLIP (por defecto `127.0.0.1:5001`). |
| `CLIP_AUTH_TOKEN` | Token compartido Laravel ↔ CLIP (obligatorio si el servicio se expone). |
| `GROQ_API_KEY` | Clave del proveedor de IA del chatbot. |

**Frontend** (`frontend/.env.production`, plantilla en `.env.production.example`):

| Variable | Uso |
|----------|-----|
| `VITE_API_URL` | URL de la API (`https://…/api`). **Obligatoria en build**: de ella se derivan todas las llamadas y las URLs de imágenes. |

---

## 10. Puesta en marcha local (resumen)

```bash
# Backend Laravel
cd backend
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed   # --seed carga lugares/eventos/noticias de ejemplo
php artisan serve --port=3000

# Motor de IA (dos procesos, en dos terminales)
python clip_service.py      # servicio Flask (puerto 5001)
python worker.py            # orquestador de la cola

# Frontend
cd frontend
npm install
npm run dev                 # Vite en http://localhost:5173 (requiere VITE_API_URL en .env)
```

> **Nota sobre `localhost`:** estos servidores escuchan en la máquina donde se
> ejecutan. Si el proyecto corre en un servidor/contenedor remoto, `localhost`
> apunta a *esa* máquina, no a la tuya: para verlo desde tu navegador usa la IP
> pública/túnel del servidor, o clona y ejecuta el proyecto en tu equipo local.

Para producción, seguir [`DEPLOY.md`](DEPLOY.md).
