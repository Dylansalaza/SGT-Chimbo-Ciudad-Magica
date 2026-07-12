<?php

namespace App\Providers;

use App\Contracts\ImageSearchInterface;
use App\Services\FlaskImageSearchService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Inversión de Dependencias: quien pida ImageSearchInterface recibe la
        // implementación Flask. Cambiar de motor = cambiar solo esta línea.
        $this->app->bind(ImageSearchInterface::class, function () {
            return new FlaskImageSearchService(
                baseUrl: rtrim(config('services.clip.url'), '/'),
                timeout: (int) config('services.clip.timeout'),
                token: config('services.clip.token') ?: null,
            );
        });
    }

    public function boot(): void
    {
        // En producción, generamos SIEMPRE URLs https (enlaces de correo, assets,
        // rutas). Evita "mixed content" y enlaces http rotos detrás del hosting.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Anti fuerza-bruta de credenciales: dos capas simultáneas.
        //  · 5 intentos/min por combinación email+IP (frena el ataque dirigido).
        //  · 20 intentos/min por IP (frena el barrido de muchos correos).
        RateLimiter::for('login', function (Request $request) {
            $email = mb_strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by($email . '|' . $request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });
    }
}
