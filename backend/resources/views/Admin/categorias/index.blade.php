@extends('admin.layouts.app')

@section('content')
<div class="p-6 space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-serif text-2xl font-extrabold text-slate-800 flex items-center gap-2"><i class="fas fa-tags text-slate-400"></i> Categorías de Lugares</h1>
            <p class="text-sm text-slate-500 mt-1">Administra las categorías disponibles al crear un lugar turístico.</p>
        </div>
        <a href="{{ route('admin.lugares.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
<i class="fas fa-arrow-left"></i> Volver a Lugares
        </a>
    </div>

    <div class="grid md:grid-cols-2 gap-6">

        {{-- Formulario para agregar categoría --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-base font-bold text-slate-700 mb-4 flex items-center gap-2"><i class="fas fa-plus text-slate-400"></i> Nueva categoría</h2>
            <form method="POST" action="{{ route('admin.categorias.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Nombre de la categoría *</label>
                    <input
                        type="text"
                        name="nombre"
                        value="{{ old('nombre') }}"
                        placeholder="Ej: Balneario, Museo, Artesanía…"
                        required
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                    >
                </div>
                <button type="submit"
                    class="w-full py-2.5 bg-[#00913f] hover:bg-green-900 text-white font-bold rounded-lg text-sm transition">
                    Crear categoría
                </button>
            </form>
        </div>

        {{-- Lista de categorías --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-base font-bold text-slate-700 mb-4 flex items-center gap-2">
                <i class="fas fa-list-ul text-slate-400"></i> Categorías existentes
                <span class="ml-2 text-xs font-semibold bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">{{ $categorias->count() }}</span>
            </h2>

            @if($categorias->isEmpty())
                <p class="text-sm text-slate-400 text-center py-8">No hay categorías aún. Crea la primera arriba.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach($categorias as $cat)
                    <li class="flex items-center justify-between py-2.5">
                        <span class="text-sm font-semibold text-slate-700">{{ $cat->nombre }}</span>
                        <form method="POST" action="{{ route('admin.categorias.destroy', $cat->id) }}"
                              onsubmit="return confirmarEliminar(this, '¿Seguro que deseas eliminar la categoría «{{ addslashes($cat->nombre) }}»? Esta acción no se puede deshacer.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="text-xs text-red-500 hover:text-red-700 font-semibold px-2 py-1 rounded hover:bg-red-50 transition">
                                Eliminar
                            </button>
                        </form>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>

    </div>
</div>
@endsection
