<?php

/**
 * Router de respaldo para el servidor embebido de PHP.
 *
 * Reemplaza el server.php del framework (vendor/.../Foundation/resources/server.php)
 * cuando ese archivo falta y `php artisan serve` no arranca.
 *
 * Uso:
 *   php -S 127.0.0.1:3000 -t public server.php
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Si la petición es a un archivo real dentro de /public (css, js, imágenes,
// /storage/...), deja que el servidor embebido lo sirva tal cual.
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

// Cualquier otra ruta la maneja Laravel.
require_once __DIR__ . '/public/index.php';
