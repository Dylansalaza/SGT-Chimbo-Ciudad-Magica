<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Las visitas se registran SOLO desde el frontend (POST /registro-visita,
        // una vez por sesión de navegador — ver App.jsx). Antes había además un
        // middleware "TrackVisits" que contaba CUALQUIER GET del grupo web como
        // una visita, incluida /storage/{ruta} (el respaldo que sirve imágenes
        // cuando el symlink falla): cada miniatura/foto cargada sumaba una fila
        // en `visits`, inflando el conteo muy por encima de las visitas reales
        // y desajustando el número público (cacheado) del número del panel
        // (en vivo). Se retiró para que solo exista UNA fuente de verdad.

        // Alias de middlewares para el panel admin
        $middleware->alias([
            'admin'     => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'rol'       => \App\Http\Middleware\EnsureRol::class,
            'presencia' => \App\Http\Middleware\EnforcePanelHeartbeat::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
