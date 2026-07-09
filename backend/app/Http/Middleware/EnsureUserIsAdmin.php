<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autorización centralizada de administradores.
 *
 * Aplica el principio de Responsabilidad Única (SRP) y DRY: en lugar de repetir
 * `if (!$request->user()->isAdmin()) abort(403)` dentro de cada acción de cada
 * controller, la regla vive en un único lugar y se compone en las rutas.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            // API → JSON; navegador (Blade) → página 403 estándar.
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Acceso denegado: se requieren privilegios de administrador.',
                ], 403);
            }

            abort(403, 'Acceso denegado: se requieren privilegios de administrador.');
        }

        return $next($request);
    }
}
