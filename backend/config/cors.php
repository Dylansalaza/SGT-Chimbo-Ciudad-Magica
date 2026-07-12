<?php

// Orígenes permitidos para las peticiones del frontend (React).
// Se leen de FRONTEND_URLS (una o varias URLs separadas por comas). Si no está
// definida (desarrollo local), se permite Vite en local.
//   Producción → FRONTEND_URLS=https://tudominio.com,https://www.tudominio.com
$frontendUrls = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('FRONTEND_URLS', ''))
)));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $frontendUrls ?: ['http://localhost:5173', 'http://127.0.0.1:5173'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];