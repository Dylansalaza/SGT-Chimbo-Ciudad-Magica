<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierre automático de la sesión del panel al CERRAR LA PESTAÑA.
 *
 * Complementa a `expire_on_close` (que cubre el cierre de TODO el navegador):
 * mientras la pestaña del panel vive, un <script> del layout admin envía un
 * "latido" (POST admin.latido) cada ~15 s. Este middleware guarda en la sesión
 * la marca de tiempo del último contacto (`panel_last_seen`). Si entre dos
 * peticiones "reales" del panel (cargar una página, guardar un formulario…)
 * pasó MÁS que la gracia configurada sin ningún latido, se asume que la pestaña
 * que mantenía viva la sesión se cerró y se cierra la sesión web.
 *
 * Por qué NO produce cierres falsos:
 *  - Recargar (F5/botón) y el botón "Atrás" recargan la página al instante y el
 *    latido se reanuda de inmediato: el hueco es de milisegundos, muy por debajo
 *    de la gracia (45 s), así que nunca disparan el cierre.
 *  - Una pestaña en segundo plano (el navegador "ahorra" y frena sus timers) que
 *    vuelve al frente late enseguida por el evento `visibilitychange`, revivien-
 *    do la sesión ANTES de cualquier navegación. Por eso la ruta del latido se
 *    EXCLUYE del chequeo de cierre: un latido siempre refresca, nunca cierra.
 */
class EnforcePanelHeartbeat
{
    public function handle(Request $request, Closure $next): Response
    {
        $ahora = time();
        $ultimo = $request->session()->get('panel_last_seen');
        $gracia = (int) config('session.tab_heartbeat_grace', 45);

        // El propio latido nunca cierra la sesión: solo refresca la presencia.
        // Así, una pestaña que regresa de segundo plano revive sin riesgo.
        $esLatido = $request->routeIs('admin.latido');

        if (! $esLatido && $ultimo !== null && ($ahora - $ultimo) > $gracia) {
            // Dejaron de llegar latidos: la pestaña del panel se cerró.
            if (Auth::guard('web')->check()) {
                Auth::guard('web')->logout();
            }
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['error' => 'sesion_cerrada_por_inactividad_de_pestana'], 401);
            }

            return redirect()->route('login')
                ->with('info', 'Tu sesión se cerró automáticamente al cerrar la pestaña del panel.');
        }

        // Marca de presencia: toda petición del panel (incluido el latido y la
        // navegación normal) refresca el reloj.
        $request->session()->put('panel_last_seen', $ahora);

        return $next($request);
    }
}
