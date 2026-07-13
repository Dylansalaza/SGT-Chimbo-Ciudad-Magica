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

echo "==> [1/5] Trayendo cambios de git..."
git pull

# --- Dependencias / migraciones (descomenta SOLO cuando el deploy las incluya) ---
# Si cambió composer.json (nuevas librerías PHP):
#   composer install --no-dev --optimize-autoloader
# Si el deploy trae migraciones de base de datos:
#   php artisan migrate --force

echo "==> [2/5] Limpiando cachés viejas (config/route/view/event)..."
php artisan optimize:clear

echo "==> [3/5] Re-cacheando para rendimiento..."
php artisan optimize

echo "==> [4/5] Recargando PHP-FPM (refresca OPcache)..."
sudo systemctl reload php8.5-fpm

echo "==> [5/5] Reiniciando workers de Supervisor..."
sudo supervisorctl restart sgt-queue sgt-worker

echo ""
echo "OK - Despliegue completado."
