{{--
    Vista de SOLO LECTURA de una noticia (botón "Ver" en el listado admin).
    Igual que eventos/show.blade.php pero para el modelo News: sin formulario,
    solo muestra los datos guardados y accesos rápidos a "Editar" o "Volver".
--}}
@extends('admin.layouts.app')

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6 max-w-3xl mx-auto">
    <div class="flex items-start justify-between gap-4 mb-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1 flex items-center gap-1.5"><i class="fas fa-eye"></i> Vista de solo lectura</p>
            <h2 class="text-2xl font-extrabold text-slate-900">{{ $noticia->title }}</h2>
        </div>
        @if($noticia->categoria)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-600 border border-red-100 uppercase tracking-wide shrink-0">
                {{ $noticia->categoria }}
            </span>
        @endif
    </div>

    @if($noticia->image_url)
        <div class="mb-5 rounded-xl overflow-hidden border border-slate-100 max-w-md">
            <img src="{{ url($noticia->image_url) }}" class="w-full h-64 object-cover">
        </div>
    @endif

    <div class="bg-slate-50 rounded-xl p-4 mb-5 max-w-xs">
        <p class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-1">Fecha de publicación</p>
        <p class="text-sm font-semibold text-slate-800">
            @if($noticia->published_at)
                {{ \Carbon\Carbon::parse($noticia->published_at)->translatedFormat('d \d\e F \d\e Y, H:i') }}
            @else
                <span class="text-amber-600">Borrador (sin publicar)</span>
            @endif
        </p>
    </div>

    <div class="mb-5">
        <p class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-1">Contenido</p>
        <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $noticia->body ?: 'Sin contenido.' }}</p>
    </div>

    @if(!empty($noticia->images))
        <div class="mb-5">
            <p class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-2">Galería adicional</p>
            <div class="flex flex-wrap gap-3">
                @foreach($noticia->images as $img)
                    <img src="{{ url($img) }}" class="w-24 h-24 object-cover rounded-lg border border-slate-100">
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-6 border-t pt-4 flex gap-2">
        <a href="{{ route('admin.noticias.edit', $noticia->id) }}" class="px-5 py-2.5 bg-black text-white font-medium rounded-lg hover:bg-slate-800 transition shadow-sm inline-flex items-center gap-2">
            <i class="fas fa-pen-to-square"></i> Editar esta noticia
        </a>
        <a href="{{ route('admin.noticias.index') }}" class="px-5 py-2.5 bg-gray-500 text-white font-medium rounded-lg hover:bg-gray-600 transition inline-flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver al listado
        </a>
    </div>
</div>
@endsection
