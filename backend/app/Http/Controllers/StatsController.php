<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Event;
use App\Models\News;
use App\Models\Gallery;
use App\Models\TouristPlace;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Estadísticas públicas de la página (visitas y contenidos).
 * Pensado para una página pública de transparencia / panel ciudadano.
 */
class StatsController extends Controller
{
    /**
     * GET /api/stats (público). El cálculo recorre 7 días + 6 meses de la
     * tabla `visits` (~16 consultas agregadas). Como es un endpoint público
     * y los números cambian poco minuto a minuto, se cachea 5 minutos: así una
     * ráfaga de visitas no dispara cientos de consultas. La llave incluye la
     * fecha de hoy para que el corte diario/mensual se recalcule al cambiar el día.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Cache::remember('stats_publicas_' . Carbon::today()->toDateString(), now()->addMinutes(5),
                fn () => $this->calcular())
        );
    }

    /** Cálculo real de las estadísticas (se ejecuta solo cuando la caché expira). */
    private function calcular(): array
    {
        $hoy = Carbon::today();

        // Tráfico de los últimos 7 días
        $semana = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $hoy->copy()->subDays($i);
            $semana[] = [
                'dia'     => ucfirst($d->isoFormat('ddd')),
                'fecha'   => $d->format('d/m'),
                'visitas' => Visit::whereDate('created_at', $d)->count(),
            ];
        }

        // Tráfico de los últimos 6 meses
        $meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $hoy->copy()->subMonths($i);
            $meses[] = [
                'mes'     => ucfirst($m->isoFormat('MMM YYYY')),
                'visitas' => Visit::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count(),
            ];
        }

        return [
            'totales' => [
                'historico' => Visit::count(),
                'mes'       => Visit::whereYear('created_at', $hoy->year)->whereMonth('created_at', $hoy->month)->count(),
                'hoy'       => Visit::whereDate('created_at', $hoy)->count(),
            ],
            'contenidos' => [
                'lugares'  => TouristPlace::count(),
                'eventos'  => Event::count(),
                'noticias' => News::count(),
                'galerias' => Gallery::count(),
            ],
            'semana' => $semana,
            'meses'  => $meses,
        ];
    }
}
