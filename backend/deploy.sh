#!/usr/bin/env bash
#
# Despliegue del backend SGT Chimbo en el servidor (Hetzner).
# Uso:  ./deploy.sh        (desde la carpeta backend/, o desde donde sea)
#
# Trae los últimos cambios de git, refresca las cachés de Laravel, recarga
# PHP-FPM (para que OPcache tome el código nuevo) y reinicia los workers.
#
set -euo pipefail

# Correr siempre desde la carpeta del script (backend/), aunque se invoque
# desde otro directorio.
cd "$(dirname "$0")"

echo "==> [1/6] Trayendo cambios de git..."
git pull

# --- Dependencias / migraciones (descomenta SOLO cuando el deploy las incluya) ---
# Si cambió composer.json (nuevas librerías PHP):
#   composer install --no-dev --optimize-autoloader
# Si el deploy trae migraciones de base de datos:
#   php artisan migrate --force

# Límite de subida de PHP-FPM: por defecto Ubuntu trae upload_max_filesize=2M,
# muy por debajo de lo que permite Nginx (client_max_body_size 20M) y de lo
# que valida Laravel (max:20480 en LugarController::importarFicha, la Ficha
# MINTUR con fotos incrustadas). Sin esto, PHP descarta el archivo ANTES de
# que Laravel llegue a validarlo (error "validation.uploaded"). Se deja en
# un .ini propio (no se toca el php.ini principal) para que sea idempotente.
echo "==> [2/6] Asegurando límite de subida en PHP-FPM (hasta 25MB)..."
sudo tee /etc/php/8.5/fpm/conf.d/99-sgt-uploads.ini > /dev/null <<'INI'
upload_max_filesize = 25M
post_max_size = 25M
INI

echo "==> [3/6] Limpiando cachés viejas (config/route/view/event)..."
php artisan optimize:clear

echo "==> [4/6] Re-cacheando para rendimiento..."
php artisan optimize

echo "==> [5/6] Recargando PHP-FPM (toma el nuevo límite de subida + OPcache)..."
sudo systemctl reload php8.5-fpm

echo "==> [6/6] Reiniciando workers de Supervisor..."
sudo supervisorctl restart sgt-queue sgt-worker

echo ""
echo "OK - Despliegue completado."
