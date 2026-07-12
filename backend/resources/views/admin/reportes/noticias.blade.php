@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    @include('admin.reportes._encabezado', [
        'titulo'    => 'Reporte de Noticias',
        'subtitulo' => 'Publicaciones informativas del portal turístico',
        'pdfRoute'  => 'admin.reportes.noticias.pdf',
        'generado'  => $generado,
    ])

    <div class="p-8 w-full space-y-6">

        <div class="grid grid-cols-3 gap-4">
            @foreach([
                ['n' => $totales['total'],    'l' => 'Noticias publicadas',  'c' => 'text-[#00913f]'],
                ['n' => $totales['mes'],      'l' => 'Publicadas este mes',   'c' => 'text-[#04521f]'],
                ['n' => count($porCategoria), 'l' => 'Categorías',            'c' => 'text-[#00913f]'],
            ] as $k)
                <div class="bg-white rounded-2xl p-5 card-premium-shadow text-center">
                    <p class="text-3xl font-black {{ $k['c'] }}">{{ number_format($k['n']) }}</p>
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 mt-1">{{ $k['l'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl p-6 card-premium-shadow">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4">Detalle de publicaciones</h2>
            @if(count($filas))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-200">
                            <th class="py-2">Título</th><th class="py-2">Categoría</th><th class="py-2">Fecha de publicación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($filas as $f)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 font-semibold text-slate-800">{{ $f['title'] }}</td>
                                <td class="py-2 text-slate-500">{{ $f['categoria'] }}</td>
                                <td class="py-2 text-slate-600">{{ $f['fecha'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <p class="text-center text-slate-400 italic py-8">No hay noticias publicadas en el sistema.</p>
            @endif
        </div>

    </div>
</div>
@endsection
