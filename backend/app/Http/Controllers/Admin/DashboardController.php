<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\News;
use App\Models\Gallery;
use App\Models\TouristPlace;
use App\Models\Visit; // Importamos el nuevo modelo de visitas
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // ==========================================
        // 1. TUS CONTADORES ORIGINALES DE MÓDULOS
        // ==========================================
        $totalEventos  = Event::count();
        $totalNoticias = News::count();
        $totalGalerias = Gallery::count();
        $totalPlaces   = TouristPlace::count(); // Renombrada para que haga juego con el Blade

        // ==========================================
        // 2. NUEVOS CONTADORES ESTADÍSTICOS DE VISITAS
        // ==========================================
        // Conteo de filas totales (Visitas históricas absolutas)
        $totalVisits = Visit::count();

        // Tráfico diario (hoy)
        $todayVisits = Visit::whereDate('created_at', Carbon::today())->count();

        // Tráfico mensual (mes actual)
        $monthVisits = Visit::whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        // ==========================================
        // 3. GENERACIÓN DEL TRÁFICO SEMANAL (GRÁFICA)
        // ==========================================
        $days = [];
        $visitCounts = [];
        
        // Ciclo para calcular los últimos 7 días con nombres en español
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            
            // Nombre del día (Lunes, Martes, etc.)
            $days[] = ucfirst($date->isoFormat('dddd')); 
            
            // Cantidad de registros acumulados en ese día específico
            $visitCounts[] = Visit::whereDate('created_at', $date)->count();
        }

        // ==========================================
        // 4. RETORNO SEGURO DE TODAS LAS VARIABLES
        // ==========================================
        return view('admin.dashboard', compact(
            'totalEventos', 
            'totalNoticias', 
            'totalGalerias', 
            'totalPlaces',
            'totalVisits',
            'monthVisits',
            'todayVisits',
            'days',
            'visitCounts'
        ));
    }
}