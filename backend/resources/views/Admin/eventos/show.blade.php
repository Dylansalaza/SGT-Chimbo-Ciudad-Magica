{{--
    Vista de SOLO LECTURA de un evento (botón "Ver" en el listado admin).
    A diferencia de edit.blade.php, aquí no hay formulario: solo se muestran
    los datos ya guardados, con accesos rápidos a "Editar" o "Volver".
--}}
@extends('admin.layouts.app')

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6 max-w-3xl mx-auto">
    <div class="flex items-start justify-between gap-4 mb-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1 flex items-center gap-1.5"><i class="fas fa-eye"></i> Vista de solo lectura</p>
            <h2 class="text-2xl font-extrabold text-slate-900">{{ $evento->title }}</h2>
        </div>
        @if($evento->categoria)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-600 border border-blue-100 uppercase tracking-wide shrink-0">
                {{ $evento->categoria }}
            </span>
        @endif
    </div>

    {{-- Detecta si la portada es un video (por extensión) para usar <video> en vez de <img> --}}
    @php
        $esVideo = $evento->image_url && \Illuminate\Support\Str::endsWith(strtolower($evento->image_url), ['.mp4', '.mov', '.webm']);
    @endphp

    @if($evento->image_url)
        <div class="mb-5 rounded-xl overflow-hidden border border-slate-100 max-w-md">
            @if($esVideo)
                <video src="{{ url($evento->image_url) }}" class="w-full h-64 object-cover" controls muted></video>
            @else
                <img src="{{ url($evento->image_url) }}" class="w-full h-64 object-cover">
            @endif
        </div>
    @endif

    <div class="grid sm:grid-cols-2 gap-4 mb-5">
        <div class="bg-slate-50 rounded-xl p-4">
            <p class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-1">Fecha de inicio</p>
            <p class="text-sm font-semibold text-slate-800">
                {{ $evento->starts_at ? \Carbon\Carbon::parse($evento->starts_at)->translatedFormat('d \d\e F \d\e Y, H:i') : 'Sin definir' }}
            </p>
        </div>
        <div class="bg-slate-50 rounded-xl p-4">
            <p class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-1">Fecha de finalización</p>
            <p class="text-sm font-semibold text-slate-800">
                {{ $evento->ends_at ? \Carbon\Carbon::parse($evento->ends_at)->translatedFormat('d \d\e F \d\e Y, H:i') : 'Sin definir' }}
            </p>
        </div>
    </div>

    <div class="mb-5">
        <p class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-1">Descripción</p>
        <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $evento->description ?: 'Sin descripción.' }}</p>
    </div>

    @if(!empty($evento->images))
        <div class="mb-5">
            <p class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-2">Galería adicional</p>
            <div class="flex flex-wrap gap-3">
                @foreach($evento->images as $img)
                    <img src="{{ url($img) }}" class="w-24 h-24 object-cover rounded-lg border border-slate-100">
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-6 border-t pt-4 flex gap-2">
        <a href="{{ route('admin.eventos.edit', $evento->id) }}" class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm inline-flex items-center gap-2">
            <i class="fas fa-pen-to-square"></i> Editar este evento
        </a>
        <a href="{{ route('admin.eventos.index') }}" class="px-5 py-2.5 bg-gray-500 text-white font-medium rounded-lg hover:bg-gray-600 transition inline-flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver al listado
        </a>
    </div>
</div>
@endsection
