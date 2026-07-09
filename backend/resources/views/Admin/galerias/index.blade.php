@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">
    
    {{-- Header de Pantalla Completa --}}
    <div class="sticky top-0 z-50 bg-[#00294d] text-white w-full px-10 py-8 shadow-lg border-b border-white/5">
        <div class="w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
            <div class="space-y-1">
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl">Galerías Fotográficas</h1>
                <p class="text-sm text-slate-300 font-medium">Módulo para subir imágenes del entorno, crear álbumes visuales y administrar fotos de portada.</p>
            </div>
            <a href="{{ route('admin.galerias.create') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black tracking-wider shadow-md transition-all uppercase self-start sm:self-center">
                <i class="fas fa-plus"></i> Nueva Galería
            </a>
        </div>
    </div>

    {{-- Contenedor de la Tabla --}}
    <div class="p-8 w-full">
        <div class="bg-white rounded-2xl p-6 card-premium-shadow w-full">
            
            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/75 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider w-20 text-center">ID</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider">Nombre del Álbum</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider">Imágenes Almacenadas</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider text-center w-48">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($galerias as $g)
                        <tr class="hover:bg-slate-50/40 transition-colors">
                            <td class="px-6 py-4 text-sm text-slate-400 font-bold text-center">{{ $g->id }}</td>
                            <td class="px-6 py-4 text-sm font-extrabold text-slate-900">{{ $g->title }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600 font-bold">
                                <span class="bg-slate-100 border border-slate-200 text-slate-700 px-2.5 py-1 rounded-md text-xs">
                                    {{ count($g->images ?? []) }} fotos
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.galerias.edit', $g->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-100/70 transition">
                                        <i class="fas fa-edit text-[10px]"></i> Editar
                                    </a>
                                    <form method="POST" action="{{ route('admin.galerias.destroy', $g->id) }}" onsubmit="return confirmarEliminar(this, '¿Seguro que deseas eliminar la galería «' + '{{ addslashes($g->title) }}' + '»? Esta acción no se puede deshacer.')">
                                        @csrf 
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-100/70 transition">
                                            <i class="fas fa-trash-alt text-[10px]"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($galerias->isEmpty())
                <div class="text-center py-12 text-slate-400 text-sm font-medium">
                    No hay galerías registradas actualmente.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection