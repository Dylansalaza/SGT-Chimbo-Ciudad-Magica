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
# Auto-actualización segura: si el propio deploy.sh cambia en el git pull, bash
# seguiría ejecutando la versión VIEJA que ya cargó en memoria (el pull crea un
# inode nuevo, pero este proceso conserva el viejo). Para evitarlo, hacemos el
# pull aquí y, si HEAD cambió, re-ejecutamos la versión nueva UNA sola vez.
if [ "${SGT_DEPLOY_REEXEC:-}" != "1" ]; then
    _antes=$(git rev-parse HEAD)
    git pull
    _despues=$(git rev-parse HEAD)
    if [ "$_antes" != "$_despues" ]; then
        echo "==> deploy.sh se actualizó en el pull; re-ejecutando la versión nueva..."
        exec env SGT_DEPLOY_REEXEC=1 bash "$0" "$@"
    fi
else
    echo "    (ya se hizo git pull; ejecutando la versión nueva del script)"
fi

# --- Dependencias / migraciones (descomenta SOLO cuando el deploy las incluya) ---
# Si cambió composer.json (nuevas librerías PHP):
#   composer install --no-dev --optimize-autoloader
# Si el deploy trae migraciones de base de datos:
#   php artisan migrate --force

# Límite de subida de PHP-FPM: por defecto Ubuntu trae upload_max_filesize=2M,
# muy por debajo de lo que permite Nginx (client_max_body_size 20M) y de lo
# que valida Laravel (20 MB en LugarController::importarFicha, la Ficha MINTUR
# con fotos incrustadas). Sin esto, PHP descarta el archivo ANTES de que
# Laravel llegue a validarlo (error "validation.uploaded"). Se deja en un .ini
# propio (no se toca el php.ini principal) para que sea idempotente. También se
# fija una carpeta temporal de subidas propia y con permisos, por si el default
# del sistema no fuera escribible por www-data (otra causa de "validation.uploaded").
echo "==> [2/6] Asegurando límite y carpeta temporal de subida en PHP-FPM..."
sudo mkdir -p /var/lib/php/sgt-uploads
sudo chown www-data:www-data /var/lib/php/sgt-uploads
sudo chmod 700 /var/lib/php/sgt-uploads
sudo tee /etc/php/8.5/fpm/conf.d/99-sgt-uploads.ini > /dev/null <<'INI'
upload_max_filesize = 25M
post_max_size = 25M
upload_tmp_dir = /var/lib/php/sgt-uploads
INI

echo "==> [3/6] Limpiando cachés viejas (config/route/view/event)..."
php artisan optimize:clear

echo "==> [4/6] Re-cacheando para rendimiento..."
php artisan optimize

# RESTART (no reload): un reload graceful de FPM NO siempre re-lee los .ini de
# conf.d, así que el nuevo límite de subida podía quedar sin aplicarse. El
# restart garantiza que el proceso arranque con la config nueva.
echo "==> [5/6] Reiniciando PHP-FPM (aplica límite de subida + refresca OPcache)..."
sudo systemctl restart php8.5-fpm

echo "==> [6/6] Reiniciando workers de Supervisor..."
sudo supervisorctl restart sgt-queue sgt-worker

# Prueba de que el límite quedó APLICADO en la config real de FPM (php-fpm -i
# lee el php.ini + conf.d del SAPI de FPM, no el de la CLI). Debe verse 25M.
echo ""
echo "--- Límite de subida efectivo en PHP-FPM ---"
php-fpm8.5 -i 2>/dev/null | grep -Ei 'upload_max_filesize|post_max_size|upload_tmp_dir' || \
  echo "(no se pudo leer php-fpm8.5 -i; verifica manualmente)"

echo ""
echo "OK - Despliegue completado."
