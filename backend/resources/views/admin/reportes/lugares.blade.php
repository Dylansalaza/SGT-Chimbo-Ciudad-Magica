@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    @include('admin.reportes._encabezado', [
        'titulo'    => 'Reporte de Lugares Turísticos',
        'subtitulo' => 'Catálogo de atractivos registrados en el sistema',
        'pdfRoute'  => 'admin.reportes.lugares.pdf',
        'generado'  => $generado,
    ])

    <div class="p-8 w-full space-y-6">

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach([
                ['n' => $totales['total'],      'l' => 'Lugares registrados', 'c' => 'text-[#00913f]'],
                ['n' => $totales['activos'],    'l' => 'Activos (públicos)',   'c' => 'text-[#04521f]'],
                ['n' => $totales['inactivos'],  'l' => 'Dados de baja',        'c' => 'text-slate-500'],
                ['n' => $totales['destacados'], 'l' => 'Destacados',           'c' => 'text-amber-600'],
            ] as $k)
                <div class="bg-white rounded-2xl p-5 card-premium-shadow text-center">
                    <p class="text-3xl font-black {{ $k['c'] }}">{{ number_format($k['n']) }}</p>
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 mt-1">{{ $k['l'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl p-6 card-premium-shadow">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4">Detalle de lugares</h2>
            @if(count($filas))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-200">
                            <th class="py-2">Nombre</th><th class="py-2">Categoría</th><th class="py-2">Dirección</th><th class="py-2">Teléfono</th><th class="py-2 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($filas as $f)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 font-semibold text-slate-800">{{ $f['nombre'] }}</td>
                                <td class="py-2 text-slate-500">{{ $f['categoria'] }}</td>
                                <td class="py-2 text-slate-600">{{ $f['direccion'] }}</td>
                                <td class="py-2 text-slate-600">{{ $f['telefono'] }}</td>
                                <td class="py-2 text-center whitespace-nowrap">
                                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $f['activo'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">{{ $f['activo'] ? 'Activo' : 'Baja' }}</span>
                                    @if($f['destacado'])<span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">Destacado</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <p class="text-center text-slate-400 italic py-8">No hay lugares turísticos registrados en el sistema.</p>
            @endif
        </div>

    </div>
</div>
@endsection
