<?php

namespace App\Providers;

use App\Contracts\ImageSearchInterface;
use App\Services\FlaskImageSearchService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Inversión de Dependencias: quien pida ImageSearchInterface recibe la
        // implementación Flask. Cambiar de motor = cambiar solo esta línea.
        $this->app->bind(ImageSearchInterface::class, function () {
            return new FlaskImageSearchService(
                baseUrl: rtrim(env('CLIP_SERVICE_URL', 'http://127.0.0.1:5001'), '/'),
                timeout: (int) env('CLIP_SERVICE_TIMEOUT', 30),
            );
        });
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
