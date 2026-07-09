<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Visit;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class TrackVisits
{
    public function handle(Request $request, Closure $next): Response
    {
        // 🚨 REGLAS DE EXCLUSIÓN CRUCIALES:
        // Solo registramos si es un método GET y NO coincide con ninguna ruta de administración o auth
        if (
            $request->isMethod('GET') && 
            !$request->is('admin*') && 
            !$request->is('api/admin*') && 
            !$request->is('api/logout') && 
            !$request->is('logout') && 
            !$request->is('login*') && 
            !$request->is('api/login*')
        ) {
            // Verificamos de forma segura que la tabla exista en PostgreSQL antes de insertar
            if (Schema::hasTable('visits')) { 
                Visit::create([
                    'ip_address'  => $request->ip(),
                    'user_agent'  => $request->userAgent(),
                    'url_visited' => $request->fullUrl(),
                ]);
            }
        }

        return $next($request);
    }
}