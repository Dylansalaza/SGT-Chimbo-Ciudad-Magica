@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    @include('admin.reportes._encabezado', [
        'titulo'    => 'Reporte de Eventos',
        'subtitulo' => 'Agenda cultural y turística registrada en el sistema',
        'pdfRoute'  => 'admin.reportes.eventos.pdf',
        'generado'  => $generado,
    ])

    <div class="p-4 sm:p-6 lg:p-8 w-full space-y-6">

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([
                ['n' => $totales['total'],    'l' => 'Eventos registrados', 'c' => 'text-[#00913f]'],
                ['n' => $totales['vigentes'], 'l' => 'Vigentes / próximos',  'c' => 'text-[#04521f]'],
                ['n' => $totales['pasados'],  'l' => 'Finalizados',          'c' => 'text-amber-600'],
                ['n' => count($porCategoria), 'l' => 'Categorías',           'c' => 'text-[#00913f]'],
            ] as $k)
                <div class="bg-white rounded-2xl p-5 card-premium-shadow text-center">
                    <p class="text-3xl font-black {{ $k['c'] }}">{{ number_format($k['n']) }}</p>
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 mt-1">{{ $k['l'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl p-6 card-premium-shadow">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4">Detalle de eventos</h2>
            @if(count($filas))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-200">
                            <th class="py-2">Evento</th><th class="py-2">Categoría</th><th class="py-2">Inicio</th><th class="py-2">Finalización</th><th class="py-2 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($filas as $f)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 font-semibold text-slate-800">{{ $f['title'] }}</td>
                                <td class="py-2 text-slate-500">{{ $f['categoria'] }}</td>
                                <td class="py-2 text-slate-600">{{ $f['inicio'] }}</td>
                                <td class="py-2 text-slate-600">{{ $f['fin'] }}</td>
                                <td class="py-2 text-center">
                                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $f['pasado'] ? 'bg-slate-100 text-slate-500' : 'bg-green-100 text-green-700' }}">{{ $f['estado'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <p class="text-center text-slate-400 italic py-8">No hay eventos registrados en el sistema.</p>
            @endif
        </div>

    </div>
</div>
@endsection
