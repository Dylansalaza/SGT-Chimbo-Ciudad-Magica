<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica que el usuario autenticado tenga uno de los roles indicados.
 * Uso en rutas:  ->middleware('rol:administrador')
 *                ->middleware('rol:admin_turismo')
 *                ->middleware('rol:administrador,admin_turismo')
 */
class EnsureRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->rol, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Acceso denegado para este rol.'], 403);
            }

            return redirect()->route('admin.dashboard')
                ->with('error', 'No tienes permiso para acceder a esa sección.');
        }

        return $next($request);
    }
}
