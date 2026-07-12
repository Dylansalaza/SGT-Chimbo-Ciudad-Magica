<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // IA del asistente virtual "Vicú" — API gratuita de Groq (modelo Llama).
    // Crea tu key en https://console.groq.com/keys y ponla en el .env.
    'groq' => [
        'key'   => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
    ],

    // Microservicio Flask/ONNX de búsqueda por imagen (CLIP). Se define aquí
    // (y no con env() suelto) para que siga funcionando con la config cacheada.
    'clip' => [
        'url'     => env('CLIP_SERVICE_URL', 'http://127.0.0.1:5001'),
        'timeout' => (int) env('CLIP_SERVICE_TIMEOUT', 30),
        // Token compartido opcional para autenticar las llamadas a Flask
        // (/search, /refresh). Debe coincidir con CLIP_AUTH_TOKEN del servicio
        // Python. Recomendado en producción si el servicio no está aislado.
        'token'   => env('CLIP_AUTH_TOKEN'),
    ],

];
