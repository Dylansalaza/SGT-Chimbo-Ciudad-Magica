@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">
    
    {{-- Header de Pantalla Completa --}}
    <div class="sticky top-0 z-50 header-corporate text-white w-full px-10 shadow-lg border-b border-white/5">
        <div class="w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
            <div class="space-y-1">
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl">Sala de Prensa y Noticias</h1>
                <p class="text-sm text-slate-300 font-medium">Panel central de publicaciones para informar a los usuarios sobre novedades de último minuto y comunicados oficiales.</p>
            </div>
            <a href="{{ route('admin.noticias.create') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl text-xs font-black tracking-wider shadow-md transition-all uppercase self-start sm:self-center">
                <i class="fas fa-plus"></i> Nueva Noticia
            </a>
        </div>
    </div>

    {{-- Contenedor de la Tabla --}}
    <div class="p-4 sm:p-6 lg:p-8 w-full">
        <div class="bg-white rounded-2xl p-6 card-premium-shadow w-full">
            
            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/75 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider w-20 text-center">ID</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider">Título del Artículo</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider">Fecha Publicación</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider text-center w-48">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($noticias as $n)
                        <tr class="hover:bg-slate-50/40 transition-colors">
                            <td class="px-6 py-4 text-sm text-slate-400 font-bold text-center">{{ $n->id }}</td>
                            <td class="px-6 py-4 text-sm font-extrabold text-slate-900">{{ $n->title }}</td>
                            <td class="px-6 py-4 text-sm font-semibold">
                                @if($n->published_at)
                                    <span class="text-green-600">{{ date('d/m/Y', strtotime($n->published_at)) }}</span>
                                @else
                                    <span class="text-amber-600 bg-amber-50 px-2.5 py-0.5 rounded-md border border-amber-100 text-xs uppercase tracking-wide">Borrador</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.noticias.edit', $n->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-black text-white hover:bg-slate-800 transition">
                                        <i class="fas fa-edit text-[10px]"></i> Editar
                                    </a>
                                    <a href="{{ route('admin.noticias.show', $n->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 border border-slate-200 transition">
                                        <i class="fas fa-eye text-[10px]"></i> Ver
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($noticias->isEmpty())
                <div class="text-center py-12 text-slate-400 text-sm font-medium">
                    No hay noticias registradas actualmente.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection