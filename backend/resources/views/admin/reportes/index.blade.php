@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    {{-- Header --}}
    <div class="sticky top-0 z-50 header-corporate text-white w-full px-10 shadow-lg border-b border-white/5">
        <div class="w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6 py-1">
            <div class="space-y-1">
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl">Reportes Institucionales</h1>
                <p class="text-sm text-slate-300 font-medium">Documentos oficiales del Sistema de Gestión Turística, listos para consultar o descargar en PDF.</p>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 lg:p-8 w-full">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

            @php
                $tarjetas = [
                    ['t' => 'Visitas',            'd' => 'Tráfico del portal: totales, visitantes únicos y evolución diaria y mensual.', 'i' => 'fa-chart-line',      'ver' => 'admin.reportes.visitas',  'pdf' => 'admin.reportes.visitas.pdf'],
                    ['t' => 'Eventos',            'd' => 'Agenda cultural: eventos registrados, vigentes y finalizados por categoría.',   'i' => 'fa-calendar-alt',    'ver' => 'admin.reportes.eventos',  'pdf' => 'admin.reportes.eventos.pdf'],
                    ['t' => 'Noticias',           'd' => 'Publicaciones informativas del portal, con fechas y categorías.',               'i' => 'fa-newspaper',       'ver' => 'admin.reportes.noticias', 'pdf' => 'admin.reportes.noticias.pdf'],
                    ['t' => 'Lugares Turísticos', 'd' => 'Catálogo de atractivos: activos, destacados y datos de contacto.',              'i' => 'fa-map-marker-alt',  'ver' => 'admin.reportes.lugares',  'pdf' => 'admin.reportes.lugares.pdf'],
                ];
            @endphp

            @foreach($tarjetas as $c)
                <div class="bg-white rounded-2xl p-6 card-premium-shadow flex flex-col">
                    <div class="w-12 h-12 rounded-xl bg-[#00913f]/10 flex items-center justify-center mb-4">
                        <i class="fas {{ $c['i'] }} text-black text-xl"></i>
                    </div>
                    <h2 class="text-lg font-black text-slate-800">Reporte de {{ $c['t'] }}</h2>
                    <p class="text-sm text-slate-500 mt-1 mb-5 flex-grow leading-relaxed">{{ $c['d'] }}</p>
                    <div class="flex items-center gap-2">
                        <a href="{{ route($c['ver']) }}" class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-4 py-2.5 rounded-lg text-xs uppercase tracking-wider transition inline-flex items-center justify-center gap-2">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                        <a href="{{ route($c['pdf']) }}" class="flex-1 text-center bg-black hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-lg text-xs uppercase tracking-wider transition inline-flex items-center justify-center gap-2 shadow">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            @endforeach

        </div>
    </div>
</div>
@endsection
