# 🚀 Guía de Despliegue — Sistema de Gestión Turística de Chimbo

Objetivo: publicar el sistema completo en internet con una **URL pública** que
cualquiera pueda usar (mapa, asistente virtual, búsqueda por imagen con IA y
panel de administración).

> ⚠️ **Antes de empezar, entiende esto:** subir el código a GitHub **no** pone
> tu sistema en línea. GitHub guarda el *código*; para que el sistema *funcione*
> en internet hay que **desplegarlo** en un servidor. Esta guía cubre ese paso.

---

## 1. Qué se va a desplegar (arquitectura)

Tu proyecto no es un sitio simple: son **5 piezas** que trabajan juntas.

```
  Navegador del usuario
         │
         ▼
  ┌──────────────┐        ┌──────────────────────────────┐
  │  FRONTEND    │  API   │           BACKEND            │
  │ React (Vite) │ ─────► │  Laravel 12 (PHP 8.2)        │
  │ Vercel       │        │  · API pública               │
  └──────────────┘        │  · Panel admin               │
                          │  · Chat IA (Groq)            │
                          └───────┬───────────┬──────────┘
                                  │           │
                        lee/escribe        crea "tickets"
                                  │           │ de búsqueda
                                  ▼           ▼
                          ┌──────────────────────────────┐
                          │   PostgreSQL (base "turismo") │◄──┐
                          └──────────────────────────────┘   │
                                                              │ FOR UPDATE
                          ┌───────────────────────┐  poll     │ SKIP LOCKED
                          │  worker.py (Python)    │ ──────────┘
                          │  procesa la imagen ────┐
                          └───────────────────────┘│ HTTP
                                                    ▼
                          ┌───────────────────────────────┐
                          │ clip_service.py (Flask+PyTorch)│
                          │ modelo CLIP (búsqueda visual)  │
                          └───────────────────────────────┘
```

| Pieza | Tecnología | Necesita |
|-------|-----------|----------|
| **Frontend** | React + Vite (estático) | Hosting estático (Vercel/Netlify) |
| **Backend** | Laravel 12, PHP 8.2 | Host PHP + `composer` |
| **Base de datos** | PostgreSQL | Postgres gestionado o en el servidor |
| **worker.py** | Python (psycopg2, requests) | Host Python + **acceso al disco de storage de Laravel** |
| **clip_service.py** | Python (Flask, **onnxruntime int8**) | Host Python con **~400 MB RAM** (modelo CLIP en ONNX) |

> ✅ **Versión ONNX activada.** El servicio de IA ya **no usa PyTorch**: corre el
> modelo CLIP como ONNX cuantizado (int8) con `onnxruntime`, bajando la RAM de
> ~2 GB a **~400 MB**. Por eso ahora cabe en un servidor pequeño y barato.

> 💡 **Cola:** tu backend usa `QUEUE_CONNECTION=database`, así que **Redis NO es
> obligatorio** para producción. Puedes omitirlo.

### La decisión que define todo el despliegue

Con ONNX, la RAM ya **no** es el problema (bajó a ~400 MB). Pero queda una
restricción de arquitectura que sigue mandando:

- **`worker.py` lee las imágenes del disco de Laravel** (`storage/app/public`),
  asumiendo que está en la **misma máquina** que el backend. En plataformas de
  contenedores aislados (Railway, Render) cada servicio tiene su propio disco, así
  que habría que migrar el almacenamiento a S3 y tocar código.

## ⭐ Mi recomendación para tu caso: **un VPS pequeño con Supervisor**

Elegiste "recomiéndame tú", así que esta es la vía más simple y fiable **para una
tesis**, dado tu stack:

- **Todo en un solo servidor** → el worker comparte disco con Laravel sin trucos.
- Con ONNX (~400 MB) basta un VPS de **2 GB de RAM** (**~€4/mes**, o gratis con
  crédito educativo de proveedores).
- Sin reescribir código para S3 ni repartir servicios: es lo más **fiel a tu
  arquitectura** y lo más robusto el día de la defensa.
- Los servicios se mantienen vivos solos con **Supervisor** (si uno se cae, se
  reinicia). No tienes que estar pendiente.

> Descarté las plataformas gestionadas (Railway/Render) como opción principal
> **solo** por el disco compartido del worker: te obligarían a migrar a S3 antes
> de que funcione. Si algún día quieres esa ruta, la dejo esbozada en la Opción B.

Sigue la **Opción A** de abajo (es exactamente esta recomendación, paso a paso).

---

## 2. Opción A (RECOMENDADA) — Un VPS con todo junto

Un VPS es un servidor Linux tuyo en la nube. Todo corre en la misma máquina, así
el worker comparte disco con Laravel y tienes RAM suficiente para CLIP.

**Proveedores y tamaño sugerido** (elige uno):
- **Hetzner Cloud** CX22 (~€4/mes, 4 GB RAM) — mejor relación precio/RAM.
- **DigitalOcean** Droplet 4 GB (~US$24/mes).
- **Contabo** VPS S (~€5/mes, 8 GB RAM).

> Pide **mínimo 2 GB de RAM** (ideal 4 GB por PyTorch). 1 GB no alcanza.

### Paso 0 — Crear el servidor
Crea un VPS con **Ubuntu 24.04 LTS**. Guarda su IP pública (ej. `203.0.113.10`)
y conéctate por SSH: `ssh root@203.0.113.10`.

### Paso 1 — Instalar dependencias del sistema
```bash
apt update && apt upgrade -y
# PHP 8.2 + extensiones que usa Laravel/PostgreSQL
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-pgsql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath unzip git \
  nginx postgresql python3 python3-pip python3-venv supervisor
# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
# Node (para construir el frontend, si lo sirves desde aquí)
curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt install -y nodejs
```

### Paso 2 — Clonar tu repositorio
```bash
cd /var/www
git clone https://github.com/TU-USUARIO/sgt-chimbo.git
cd sgt-chimbo
```

### Paso 3 — PostgreSQL
```bash
sudo -u postgres psql -c "CREATE DATABASE turismo;"
sudo -u postgres psql -c "CREATE USER sgt WITH ENCRYPTED PASSWORD 'UNA_CLAVE_FUERTE';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE turismo TO sgt;"
sudo -u postgres psql -d turismo -c "GRANT ALL ON SCHEMA public TO sgt;"
```

### Paso 4 — Backend Laravel
```bash
cd /var/www/sgt-chimbo/backend
composer install --no-dev --optimize-autoloader
# Usa la plantilla de PRODUCCIÓN (trae ya los valores seguros):
cp .env.production.example .env
php artisan key:generate
```
Ahora edita `.env` (con `nano .env`) y rellena los `<...>`. La plantilla
`.env.production.example` documenta cada variable; lo mínimo imprescindible:
```dotenv
APP_ENV=production
APP_DEBUG=false                          # ⚠️ nunca true en producción
APP_URL=https://api.tudominio.com        # o http://TU_IP

# Orígenes del frontend permitidos por CORS (separa varios con comas).
FRONTEND_URLS=https://tudominio.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=turismo
DB_USERNAME=sgt
DB_PASSWORD=UNA_CLAVE_FUERTE

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=file                         # ⚡ NO uses "database": guardar el caché en
                                         # PostgreSQL abre una conexión a la BD por
                                         # request (~200ms) y bajo carga colapsa
                                         # (100 peticiones simultáneas → ~5s). Con
                                         # "file" el mismo caso baja a ~90ms (60x).
                                         # Si algún día escalas a varios servidores,
                                         # usa "redis" (aún más rápido).
SESSION_SECURE_COOKIE=true               # cookie solo por HTTPS

# Correo (recuperación de contraseña) — Gmail app-password de 16 caracteres
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=tucorreo@gmail.com
MAIL_PASSWORD=app_password_16_chars
MAIL_FROM_ADDRESS=tucorreo@gmail.com

# Asistente virtual (IA de chat) — obtén la key en https://console.groq.com/keys
GROQ_API_KEY=tu_key_de_groq
GROQ_MODEL=llama-3.1-8b-instant

# Servicio CLIP interno + token compartido (protege /search y /refresh).
# Genera el token con:  php artisan key:generate --show   (parte tras "base64:")
CLIP_SERVICE_URL=http://127.0.0.1:5001
CLIP_AUTH_TOKEN=un_token_largo_aleatorio
```
> El servicio Python (Paso 5) debe arrancar con la MISMA variable
> `CLIP_AUTH_TOKEN` en su entorno para que backend ↔ motor de IA se autentiquen.
Crea la estructura de la BD, datos de ejemplo y el enlace de storage:
```bash
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache && php artisan route:cache
chown -R www-data:www-data storage bootstrap/cache
```

### Paso 5 — Servicios Python (CLIP en ONNX + worker)

**5.1 — Generar el modelo ONNX (UNA vez, en TU PC — no en el servidor).**
Tu PC ya tiene PyTorch; el servidor no lo necesitará.
```bash
# En tu PC (Windows), dentro de backend/
python -m venv .venv-export
.venv-export\Scripts\activate
pip install -r requirements-export.txt
python scripts/export_clip_onnx.py
```
Esto crea `backend/models/clip_image_int8.onnx` (~40–90 MB) y **verifica** que
los vectores siguen siendo equivalentes a PyTorch (similitud coseno > 0.99).

**5.2 — Subir el modelo al servidor** (no está en Git por su tamaño):
```bash
# Desde tu PC
scp backend/models/clip_image_int8.onnx root@TU_IP:/var/www/sgt-chimbo/backend/models/
```

**5.3 — Instalar el runtime ligero en el servidor** (sin PyTorch):
```bash
cd /var/www/sgt-chimbo/backend
python3 -m venv .venv
.venv/bin/pip install --upgrade pip
.venv/bin/pip install -r requirements.txt   # flask, onnxruntime, pillow, numpy…
```
> Sin `torch` ni `transformers`: la instalación es pequeña y `clip_service.py`
> arranca usando ~400 MB de RAM.

### Paso 6 — Mantener todo corriendo con Supervisor
Crea `/etc/supervisor/conf.d/sgt.conf`:
```ini
[program:sgt-clip]
command=/var/www/sgt-chimbo/backend/.venv/bin/python clip_service.py
directory=/var/www/sgt-chimbo/backend
autostart=true
autorestart=true
stdout_logfile=/var/log/sgt-clip.log
stderr_logfile=/var/log/sgt-clip.err.log
; clip_service.py lee su config del ENTORNO (no del .env de Laravel).
; El token DEBE ser el MISMO que CLIP_AUTH_TOKEN del backend/.env.
; CLIP_HOST=127.0.0.1 lo mantiene interno (que Nginx/Laravel lo llamen local).
environment=CLIP_HOST="127.0.0.1",CLIP_PORT="5001",LARAVEL_URL="http://127.0.0.1:8000",CLIP_AUTH_TOKEN="el_mismo_token_del_.env"

[program:sgt-worker]
command=/var/www/sgt-chimbo/backend/.venv/bin/python worker.py
directory=/var/www/sgt-chimbo/backend
autostart=true
autorestart=true
stdout_logfile=/var/log/sgt-worker.log

[program:sgt-queue]
command=php artisan queue:work --sleep=3 --tries=3
directory=/var/www/sgt-chimbo/backend
autostart=true
autorestart=true
stdout_logfile=/var/log/sgt-queue.log
```
```bash
supervisorctl reread && supervisorctl update && supervisorctl status
```

### Paso 7 — Nginx (servir la API por HTTP/HTTPS)
Crea `/etc/nginx/sites-available/sgt` apuntando a `backend/public`:
```nginx
server {
    listen 80;
    server_name api.tudominio.com;   # o la IP del VPS
    root /var/www/sgt-chimbo/backend/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
    client_max_body_size 20M;   # permitir subir imágenes
}
```
```bash
ln -s /etc/nginx/sites-available/sgt /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
# HTTPS gratis (si tienes dominio):
apt install -y certbot python3-certbot-nginx
certbot --nginx -d api.tudominio.com
```

### Paso 8 — CORS (¡importante!)
Ya **no** hace falta editar `config/cors.php`: ahora lee los orígenes permitidos
de la variable `FRONTEND_URLS` del `.env`. Solo define la URL pública de tu
frontend (o el navegador **bloqueará** las llamadas):
```dotenv
# En backend/.env  (separa varias URLs con comas)
FRONTEND_URLS=https://sgt-chimbo.vercel.app
```
Luego: `php artisan config:cache`.
> Si no defines `FRONTEND_URLS`, en local sigue permitiendo `localhost:5173`.

---

## 3. Frontend en Vercel (gratis)

El frontend es estático, así que Vercel es ideal y gratuito.

1. Entra a [vercel.com](https://vercel.com) e inicia sesión con GitHub.
2. **Add New → Project** → importa el repo `sgt-chimbo`.
3. Configura:
   - **Root Directory:** `frontend`
   - **Framework Preset:** Vite
   - **Build Command:** `npm run build`
   - **Output Directory:** `dist`
4. En **Environment Variables** añade:
   ```
   VITE_API_URL = https://api.tudominio.com/api
   ```
   (la URL de tu backend + `/api`). Si no tienes dominio, usa `http://TU_IP/api`
   — pero ojo: Vercel es HTTPS y un backend HTTP dará "mixed content". Para un
   demo serio, **consigue un dominio y HTTPS** en el backend.
5. **Deploy.** Vercel te da una URL pública tipo `https://sgt-chimbo.vercel.app`.

> Recuerda volver al Paso 8 y añadir esa URL de Vercel a `allowed_origins`.

---

## 4. ¿Qué pasa con tus datos actuales?

`php artisan migrate --seed` crea la estructura + los **lugares de ejemplo** del
seeder. Si quieres llevar también el **contenido real** que ya cargaste en tu
Postgres local (lugares, noticias, eventos, imágenes):

**Datos (tablas):**
```bash
# En tu PC (Windows), exporta:
pg_dump -U postgres -d turismo --data-only --inserts > datos.sql
# Sube datos.sql al VPS (scp) y en el VPS impórtalo:
psql -U sgt -d turismo -f datos.sql
```

**Imágenes subidas** (viven en `backend/storage/app/public`, que **no** está en
Git): cópialas aparte con `scp -r` desde tu PC al mismo directorio del VPS.

---

## 5. Verificación final (checklist)

- [ ] `https://api.tudominio.com/api/tourist-places` devuelve JSON con lugares.
- [ ] `supervisorctl status` muestra `sgt-clip`, `sgt-worker`, `sgt-queue` en `RUNNING`.
- [ ] La URL de Vercel abre el portal y el mapa carga los marcadores.
- [ ] El asistente virtual responde (menús + IA de chat con Groq).
- [ ] Subir una foto en el mapa devuelve un lugar identificado (prueba el flujo CLIP).
- [ ] El panel admin (`/login` del backend) entra correctamente.

---

## 6. Opción B (alternativa) — Plataforma gestionada (Railway + Vercel)

Si prefieres no administrar un servidor Linux, [Railway](https://railway.app)
puede alojar varios servicios en un proyecto:
- Servicio **PostgreSQL** (1 clic).
- Servicio **Laravel** (build con Nixpacks).
- Servicio **Python** para `clip_service.py` (necesita plan con RAM suficiente).
- Servicio **Python** para `worker.py`.

⚠️ **Contras para tu caso:** el worker espera leer las imágenes del **disco local
de Laravel**; en Railway cada servicio está aislado. Para que funcione en
contenedores separados habría que **migrar el almacenamiento a S3** (ya tienes
las variables `AWS_*` en el `.env`) y ajustar `worker.py` para leer la imagen por
URL/S3 en vez de disco. Es más trabajo de código. Por eso, para un demo fiel al
código actual, **la Opción A (VPS) es más directa**.

---

## 7. Costo y RAM — resumen honesto

| Recurso | Mínimo | Recomendado | Costo aprox. |
|---------|--------|-------------|--------------|
| VPS (todo el backend + IA en ONNX) | 1 GB RAM | 2 GB RAM | €3–4/mes |
| Frontend (Vercel) | — | — | Gratis |
| Groq (chat IA) | — | — | Gratis (con límites) |
| Dominio (para HTTPS) | — | 1 dominio | ~US$10/año |

> Gracias a ONNX, el servicio de IA pasó de necesitar ~2 GB a ~400 MB, así que un
> VPS de **2 GB** cubre todo (backend + Postgres + IA) con margen de sobra.

> Si el presupuesto es un problema o solo necesitas mostrarlo el día de la
> defensa, existe una versión **sin la búsqueda por imagen (CLIP)** que cabe en
> hosting mucho más barato. Pídemela y te preparo esa variante.

---

## 8. Próximos pasos que puedo hacer por ti

- Generar los archivos reales de configuración (`requirements.txt`, `.env.production`
  de ejemplo, config de Nginx, `sgt.conf` de Supervisor) ya listos en el repo.
- Preparar `Dockerfile` + `docker-compose.yml` si prefieres desplegar con Docker.
- Ajustar `config/cors.php` para leer los orígenes desde una variable de entorno
  (más limpio que editarlo a mano).
- Adaptar `worker.py` a almacenamiento S3 si eliges la Opción B (Railway).

Dime cuál y lo dejamos listo.
