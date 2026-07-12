@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    @include('admin.reportes._encabezado', [
        'titulo'    => 'Reporte de Visitas',
        'subtitulo' => 'Estadísticas de tráfico del portal turístico de San José de Chimbo',
        'pdfRoute'  => 'admin.reportes.visitas.pdf',
        'csvRoute'  => 'admin.reportes.visitas.csv',
        'generado'  => $generado,
    ])

    <div class="p-8 w-full space-y-6">

        {{-- KPIs --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([
                ['n' => $totales['historico'], 'l' => 'Visitas totales',   'c' => 'text-[#00913f]'],
                ['n' => $totales['unicos'],    'l' => 'Visitantes únicos',  'c' => 'text-[#04521f]'],
                ['n' => $totales['mes'],       'l' => 'Este mes',           'c' => 'text-[#00913f]'],
                ['n' => $totales['hoy'],       'l' => 'Hoy',                'c' => 'text-amber-600'],
            ] as $k)
                <div class="bg-white rounded-2xl p-5 card-premium-shadow text-center">
                    <p class="text-3xl font-black {{ $k['c'] }}">{{ number_format($k['n']) }}</p>
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 mt-1">{{ $k['l'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Mensual --}}
        <div class="bg-white rounded-2xl p-6 card-premium-shadow">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4">Visitas por mes · últimos 12 meses</h2>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-200">
                        <th class="py-2 w-1/3">Mes</th><th class="py-2">Distribución</th><th class="py-2 text-right w-24">Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mensual as $m)
                        @php $pct = $maxMes > 0 ? max(2, round($m['visitas'] * 100 / $maxMes)) : 0; @endphp
                        <tr class="border-b border-slate-100">
                            <td class="py-2 text-slate-700">{{ $m['mes'] }}</td>
                            <td class="py-2 pr-4"><div class="bg-slate-100 rounded-full h-2.5"><div class="bg-[#00913f] h-2.5 rounded-full" style="width: {{ $pct }}%"></div></div></td>
                            <td class="py-2 text-right font-bold text-slate-800">{{ number_format($m['visitas']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Diario --}}
        <div class="bg-white rounded-2xl p-6 card-premium-shadow">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4">Detalle diario · últimos 30 días</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-200">
                            <th class="py-2">Fecha</th><th class="py-2">Día</th><th class="py-2 text-right">Visitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($diario as $d)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 text-slate-700">{{ $d['fecha'] }}</td>
                                <td class="py-2 text-slate-500">{{ $d['dia'] }}</td>
                                <td class="py-2 text-right font-bold text-slate-800">{{ number_format($d['visitas']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
