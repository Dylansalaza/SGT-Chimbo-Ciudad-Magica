<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Carbon\Carbon;

/**
 * Reportes de visitas a la página (diario, mensual y totales).
 * La vista está optimizada para imprimir / guardar como PDF.
 */
class ReporteController extends Controller
{
    public function visitas()
    {
        $hoy = Carbon::today();

        // Totales
        $totales = [
            'historico' => Visit::count(),
            'unicos'    => Visit::distinct('ip_address')->count('ip_address'),
            'hoy'       => Visit::whereDate('created_at', $hoy)->count(),
            'mes'       => Visit::whereYear('created_at', $hoy->year)->whereMonth('created_at', $hoy->month)->count(),
        ];

        // Últimos 30 días (diario)
        $diario = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $hoy->copy()->subDays($i);
            $diario[] = [
                'fecha'    => $d->format('d/m/Y'),
                'dia'      => ucfirst($d->isoFormat('dddd')),
                'visitas'  => Visit::whereDate('created_at', $d)->count(),
            ];
        }

        // Últimos 12 meses (mensual)
        $mensual = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = $hoy->copy()->subMonths($i);
            $mensual[] = [
                'mes'     => ucfirst($m->isoFormat('MMMM YYYY')),
                'visitas' => Visit::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count(),
            ];
        }

        $generado = Carbon::now()->isoFormat('D [de] MMMM [de] YYYY, HH:mm');

        return view('admin.reportes.visitas', compact('totales', 'diario', 'mensual', 'generado'));
    }

    /**
     * Exporta el detalle diario (últimos 90 días) en CSV (se abre en Excel).
     */
    public function visitasCsv()
    {
        $hoy = Carbon::today();
        $filas = [];
        for ($i = 89; $i >= 0; $i--) {
            $d = $hoy->copy()->subDays($i);
            $filas[] = [
                $d->format('Y-m-d'),
                ucfirst($d->isoFormat('dddd')),
                Visit::whereDate('created_at', $d)->count(),
            ];
        }

        $nombre = 'reporte_visitas_' . $hoy->format('Y-m-d') . '.csv';

        $callback = function () use ($filas) {
            $out = fopen('php://output', 'w');
            // BOM para que Excel reconozca acentos en UTF-8
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Fecha', 'Dia', 'Visitas']);
            foreach ($filas as $f) {
                fputcsv($out, $f);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
