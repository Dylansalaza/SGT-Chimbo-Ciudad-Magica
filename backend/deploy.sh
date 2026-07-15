#!/usr/bin/env bash
#
# Despliegue del backend SGT Chimbo en el servidor (Hetzner).
# Uso:  ./deploy.sh        (desde la carpeta backend/, o desde donde sea)
#
# Trae los últimos cambios de git, ajusta la config del .env que debe ser igual
# en todos los despliegues, refresca las cachés de Laravel, recarga PHP-FPM
# (para que OPcache tome el código nuevo) y reinicia los workers.
#
# CORREO (recuperación de contraseña): la contraseña de aplicación de Gmail es un
# SECRETO y por eso NO está escrita en este archivo — este script vive en un repo
# de GitHub PÚBLICO, así que cualquier credencial aquí queda publicada a internet
# y no se puede borrar del historial. Para fijarla, pásala por variable de entorno
# UNA sola vez (queda guardada en el .env del servidor, que no está en git):
#
#     SGT_MAIL_PASSWORD='xxxxxxxxxxxxxxxx' ./deploy.sh
#
# Los despliegues siguientes ya son solo ./deploy.sh: el script comprueba que la
# contraseña siga puesta y avisa si falta.
#
set -euo pipefail

# Correr siempre desde la carpeta del script (backend/), aunque se invoque
# desde otro directorio.
cd "$(dirname "$0")"

echo "==> [1/7] Trayendo cambios de git..."
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

# El .env NO está en git (tiene secretos), así que los valores de config que
# deben ser iguales en todos los despliegues se fijan aquí. Es idempotente:
# reemplaza la clave si ya existe y la añade si no.
poner_en_env() {
    local clave="$1" valor="$2"
    if grep -qE "^${clave}=" .env; then
        # El valor va por variable de entorno de awk, no interpolado en el
        # patrón, para que un secreto con /, & o | no rompa la sustitución.
        awk -v c="$clave" -v v="$valor" \
            'BEGIN{FS=OFS="="} $1==c{print c "=" v; next} {print}' .env > .env.tmp
        mv .env.tmp .env
    else
        printf '%s=%s\n' "$clave" "$valor" >> .env
    fi
}

echo "==> [2/7] Ajustando configuración en el .env del servidor..."
if [ ! -f .env ]; then
    echo "ERROR: no existe backend/.env en el servidor. Créalo a partir de .env.example." >&2
    exit 1
fi

# Sesión del panel: 30 min de INACTIVIDAD (decisión del administrador, 2026-07-15).
poner_en_env SESSION_LIFETIME 30
echo "    SESSION_LIFETIME=30"

# Contraseña de aplicación de Gmail: solo se escribe si se pasó por entorno en
# esta corrida. Si no, se deja la que ya tenga el .env del servidor.
if [ -n "${SGT_MAIL_PASSWORD:-}" ]; then
    poner_en_env MAIL_PASSWORD "$SGT_MAIL_PASSWORD"
    chmod 600 .env
    echo "    MAIL_PASSWORD actualizada desde SGT_MAIL_PASSWORD"
fi

# Aviso si el correo no puede funcionar: sin esto, la recuperación de contraseña
# falla en silencio para el usuario ("no pudimos enviar el correo") y el motivo
# real solo aparece en storage/logs/laravel.log.
_mail_pass=$(grep -E '^MAIL_PASSWORD=' .env | cut -d= -f2- | tr -d '"' | tr -d "'" || true)
if [ -z "$_mail_pass" ] || [ "$_mail_pass" = "app_password_16_chars" ]; then
    echo "    ⚠  AVISO: MAIL_PASSWORD no está configurada en el .env."
    echo "       La recuperación de contraseña NO enviará correos."
    echo "       Arréglalo con:  SGT_MAIL_PASSWORD='tu_app_password' ./deploy.sh"
fi

# Límite de subida de PHP-FPM: por defecto Ubuntu trae upload_max_filesize=2M,
# muy por debajo de lo que permite Nginx (client_max_body_size 20M) y de lo
# que valida Laravel (20 MB en LugarController::importarFicha, la Ficha MINTUR
# con fotos incrustadas). Sin esto, PHP descarta el archivo ANTES de que
# Laravel llegue a validarlo (error "validation.uploaded"). Se deja en un .ini
# propio (no se toca el php.ini principal) para que sea idempotente. También se
# fija una carpeta temporal de subidas propia y con permisos, por si el default
# del sistema no fuera escribible por www-data (otra causa de "validation.uploaded").
echo "==> [3/7] Asegurando límite y carpeta temporal de subida en PHP-FPM..."
sudo mkdir -p /var/lib/php/sgt-uploads
sudo chown www-data:www-data /var/lib/php/sgt-uploads
sudo chmod 700 /var/lib/php/sgt-uploads
sudo tee /etc/php/8.5/fpm/conf.d/99-sgt-uploads.ini > /dev/null <<'INI'
upload_max_filesize = 25M
post_max_size = 25M
upload_tmp_dir = /var/lib/php/sgt-uploads
INI

echo "==> [4/7] Limpiando cachés viejas (config/route/view/event)..."
php artisan optimize:clear

echo "==> [5/7] Re-cacheando para rendimiento..."
php artisan optimize

# RESTART (no reload): un reload graceful de FPM NO siempre re-lee los .ini de
# conf.d, así que el nuevo límite de subida podía quedar sin aplicarse. El
# restart garantiza que el proceso arranque con la config nueva.
echo "==> [6/7] Reiniciando PHP-FPM (aplica límite de subida + refresca OPcache)..."
sudo systemctl restart php8.5-fpm

echo "==> [7/7] Reiniciando workers de Supervisor..."
sudo supervisorctl restart sgt-queue sgt-worker

# Prueba de que el límite quedó APLICADO en la config real de FPM (php-fpm -i
# lee el php.ini + conf.d del SAPI de FPM, no el de la CLI). Debe verse 25M.
echo ""
echo "--- Límite de subida efectivo en PHP-FPM ---"
php-fpm8.5 -i 2>/dev/null | grep -Ei 'upload_max_filesize|post_max_size|upload_tmp_dir' || \
  echo "(no se pudo leer php-fpm8.5 -i; verifica manualmente)"

echo ""
echo "OK - Despliegue completado."
