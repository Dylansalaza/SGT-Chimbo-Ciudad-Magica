<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Event;
use App\Models\News;
use App\Models\Gallery;
use App\Models\TouristPlace;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * Estadísticas públicas de la página (visitas y contenidos).
 * Pensado para una página pública de transparencia / panel ciudadano.
 */
class StatsController extends Controller
{
    public function index(): JsonResponse
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

        return response()->json([
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
        ]);
    }
}
