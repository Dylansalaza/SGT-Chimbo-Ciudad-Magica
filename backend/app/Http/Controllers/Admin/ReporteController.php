<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\News;
use App\Models\TouristPlace;
use App\Models\Visit;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

/**
 * Reportes institucionales del Sistema de Gestión Turística.
 *
 * Cada reporte tiene DOS salidas que comparten el mismo generador de datos:
 *   · vista en pantalla (dentro del panel) para previsualizar.
 *   · PDF formal generado en el servidor con dompdf (membrete municipal,
 *     tablas, totales, numeración de página y área de firma).
 *
 * Además se conserva la exportación CSV del detalle de visitas (abre en Excel).
 */
class ReporteController extends Controller
{
    /** Opciones comunes de dompdf para todos los reportes. */
    private function pdf(string $vista, array $datos)
    {
        return Pdf::loadView($vista, $datos)
            ->setPaper('a4', 'portrait')
            ->setOption(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
    }

    // ────────────────────────────────────────────────────────────────────
    //  HUB DE REPORTES
    // ────────────────────────────────────────────────────────────────────
    public function index()
    {
        return view('admin.reportes.index');
    }

    // ────────────────────────────────────────────────────────────────────
    //  VISITAS
    // ────────────────────────────────────────────────────────────────────
    private function datosVisitas(): array
    {
        $hoy = Carbon::today();

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
                'fecha'   => $d->format('d/m/Y'),
                'dia'     => ucfirst($d->isoFormat('dddd')),
                'visitas' => Visit::whereDate('created_at', $d)->count(),
            ];
        }

        // Últimos 12 meses (mensual)
        $mensual = [];
        $maxMes  = 0;
        for ($i = 11; $i >= 0; $i--) {
            $m = $hoy->copy()->subMonths($i);
            $n = Visit::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count();
            $mensual[] = ['mes' => ucfirst($m->isoFormat('MMMM YYYY')), 'visitas' => $n];
            $maxMes = max($maxMes, $n);
        }

        return [
            'totales'  => $totales,
            'diario'   => $diario,
            'mensual'  => $mensual,
            'maxMes'   => $maxMes,
            'periodo'  => 'Del ' . $hoy->copy()->subDays(29)->isoFormat('D [de] MMMM [de] YYYY')
                        . ' al ' . $hoy->isoFormat('D [de] MMMM [de] YYYY'),
            'generado' => Carbon::now()->isoFormat('D [de] MMMM [de] YYYY, HH:mm'),
        ];
    }

    public function visitas()
    {
        return view('admin.reportes.visitas', $this->datosVisitas());
    }

    public function visitasPdf()
    {
        $datos = $this->datosVisitas();
        return $this->pdf('admin.reportes.pdf.visitas', $datos)
            ->download('reporte-visitas-' . Carbon::today()->format('Y-m-d') . '.pdf');
    }

    // ────────────────────────────────────────────────────────────────────
    //  EVENTOS
    // ────────────────────────────────────────────────────────────────────
    private function datosEventos(): array
    {
        $ahora   = Carbon::now();
        $eventos = Event::orderByDesc('starts_at')->get();

        $filas = $eventos->map(function ($e) use ($ahora) {
            $ref     = $e->ends_at ?? $e->starts_at;
            $pasado  = $ref && Carbon::parse($ref)->lt($ahora);
            return [
                'title'     => $e->title,
                'categoria' => $e->categoria ?: '—',
                'inicio'    => $e->starts_at ? Carbon::parse($e->starts_at)->isoFormat('D/MM/YYYY HH:mm') : '—',
                'fin'       => $e->ends_at ? Carbon::parse($e->ends_at)->isoFormat('D/MM/YYYY HH:mm') : '—',
                'estado'    => $pasado ? 'Finalizado' : 'Vigente',
                'pasado'    => $pasado,
            ];
        })->all();

        return [
            'filas'   => $filas,
            'totales' => [
                'total'     => count($filas),
                'vigentes'  => collect($filas)->where('pasado', false)->count(),
                'pasados'   => collect($filas)->where('pasado', true)->count(),
            ],
            'porCategoria' => $eventos->groupBy(fn ($e) => $e->categoria ?: 'Sin categoría')
                                      ->map->count()->sortDesc()->all(),
            'generado' => Carbon::now()->isoFormat('D [de] MMMM [de] YYYY, HH:mm'),
        ];
    }

    public function eventos()
    {
        return view('admin.reportes.eventos', $this->datosEventos());
    }

    public function eventosPdf()
    {
        return $this->pdf('admin.reportes.pdf.eventos', $this->datosEventos())
            ->download('reporte-eventos-' . Carbon::today()->format('Y-m-d') . '.pdf');
    }

    // ────────────────────────────────────────────────────────────────────
    //  NOTICIAS
    // ────────────────────────────────────────────────────────────────────
    private function datosNoticias(): array
    {
        $hoy      = Carbon::today();
        $noticias = News::orderByDesc('published_at')->get();

        $filas = $noticias->map(fn ($n) => [
            'title'     => $n->title,
            'categoria' => $n->categoria ?: '—',
            'fecha'     => $n->published_at ? Carbon::parse($n->published_at)->isoFormat('D [de] MMMM [de] YYYY') : '—',
        ])->all();

        return [
            'filas'   => $filas,
            'totales' => [
                'total' => count($filas),
                'mes'   => $noticias->filter(fn ($n) => $n->published_at
                            && Carbon::parse($n->published_at)->year === $hoy->year
                            && Carbon::parse($n->published_at)->month === $hoy->month)->count(),
            ],
            'porCategoria' => $noticias->groupBy(fn ($n) => $n->categoria ?: 'Sin categoría')
                                       ->map->count()->sortDesc()->all(),
            'generado' => Carbon::now()->isoFormat('D [de] MMMM [de] YYYY, HH:mm'),
        ];
    }

    public function noticias()
    {
        return view('admin.reportes.noticias', $this->datosNoticias());
    }

    public function noticiasPdf()
    {
        return $this->pdf('admin.reportes.pdf.noticias', $this->datosNoticias())
            ->download('reporte-noticias-' . Carbon::today()->format('Y-m-d') . '.pdf');
    }

    // ────────────────────────────────────────────────────────────────────
    //  LUGARES TURÍSTICOS
    // ────────────────────────────────────────────────────────────────────
    private function datosLugares(): array
    {
        $lugares = TouristPlace::orderBy('nombre')->get();

        $filas = $lugares->map(fn ($l) => [
            'nombre'    => $l->nombre,
            'categoria' => $l->categoria ?: '—',
            'direccion' => $l->direccion ?: '—',
            'telefono'  => $l->telefono ?: '—',
            'activo'    => (bool) $l->activo,
            'destacado' => (bool) $l->destacado,
        ])->all();

        return [
            'filas'   => $filas,
            'totales' => [
                'total'     => count($filas),
                'activos'   => collect($filas)->where('activo', true)->count(),
                'inactivos' => collect($filas)->where('activo', false)->count(),
                'destacados'=> collect($filas)->where('destacado', true)->count(),
            ],
            'porCategoria' => $lugares->groupBy(fn ($l) => $l->categoria ?: 'Sin categoría')
                                      ->map->count()->sortDesc()->all(),
            'generado' => Carbon::now()->isoFormat('D [de] MMMM [de] YYYY, HH:mm'),
        ];
    }

    public function lugares()
    {
        return view('admin.reportes.lugares', $this->datosLugares());
    }

    public function lugaresPdf()
    {
        return $this->pdf('admin.reportes.pdf.lugares', $this->datosLugares())
            ->download('reporte-lugares-' . Carbon::today()->format('Y-m-d') . '.pdf');
    }

    // ────────────────────────────────────────────────────────────────────
    //  CSV (detalle diario de visitas, se abre en Excel)
    // ────────────────────────────────────────────────────────────────────
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
            fwrite($out, "\xEF\xBB\xBF"); // BOM para acentos en Excel
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
