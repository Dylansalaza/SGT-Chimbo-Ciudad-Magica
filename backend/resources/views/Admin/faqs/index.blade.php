@extends('admin.layouts.app')

@section('content')
<div class="p-6 space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-serif text-2xl font-extrabold text-slate-800 flex items-center gap-2"><i class="fas fa-comment-dots text-slate-400"></i> Palabras clave del Asistente Virtual</h1>
            <p class="text-sm text-slate-500 mt-1">Cuando un visitante escriba texto libre en el chat, el asistente buscará estas palabras clave dentro de su mensaje y responderá con el texto configurado.</p>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">

        {{-- Formulario para agregar --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 h-fit">
            <h2 class="text-base font-bold text-slate-700 mb-4 flex items-center gap-2"><i class="fas fa-plus text-slate-400"></i> Nueva palabra clave</h2>
            <form method="POST" action="{{ route('admin.faqs.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Palabra o frase clave *</label>
                    <input
                        type="text"
                        name="keyword"
                        value="{{ old('keyword') }}"
                        placeholder="Ej: horario, precio, wifi, baños…"
                        required
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <p class="text-xs text-slate-400 mt-1">El asistente responde si el mensaje del visitante contiene esta palabra (sin importar mayúsculas/acentos).</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Respuesta *</label>
                    <textarea
                        name="answer"
                        rows="4"
                        placeholder="Texto que el asistente responderá…"
                        required
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >{{ old('answer') }}</textarea>
                </div>
                <button type="submit"
                    class="w-full py-2.5 bg-[#00294d] hover:bg-blue-900 text-white font-bold rounded-lg text-sm transition">
                    Crear palabra clave
                </button>
            </form>
        </div>

        {{-- Lista existente --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-base font-bold text-slate-700 mb-4 flex items-center gap-2">
                <i class="fas fa-list-ul text-slate-400"></i> Palabras clave existentes
                <span class="ml-2 text-xs font-semibold bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">{{ $faqs->count() }}</span>
            </h2>

            @if($faqs->isEmpty())
                <p class="text-sm text-slate-400 text-center py-8">Aún no hay ninguna. Crea la primera a la izquierda.</p>
            @else
                <div class="space-y-3 max-h-[32rem] overflow-y-auto pr-1">
                    @foreach($faqs as $faq)
                    <div class="faq-item border border-slate-200 rounded-xl p-3.5">
                        <div class="faq-vista">
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-sm font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded">{{ $faq->keyword }}</span>
                                <div class="flex gap-1 shrink-0">
                                    <button type="button" onclick="toggleEditarFaq(this, true)"
                                        class="text-xs text-slate-500 hover:text-blue-700 font-semibold px-2 py-1 rounded hover:bg-blue-50 transition">
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('admin.faqs.destroy', $faq->id) }}"
                                          onsubmit="return confirmarEliminar(this, '¿Eliminar la palabra clave «{{ addslashes($faq->keyword) }}»?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="text-xs text-red-500 hover:text-red-700 font-semibold px-2 py-1 rounded hover:bg-red-50 transition">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <p class="text-sm text-slate-600 mt-1.5 whitespace-pre-wrap">{{ $faq->answer }}</p>
                        </div>

                        <form class="faq-editar hidden space-y-2" method="POST" action="{{ route('admin.faqs.update', $faq->id) }}">
                            @csrf
                            @method('PUT')
                            <input type="text" name="keyword" value="{{ $faq->keyword }}" required
                                class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm font-bold text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <textarea name="answer" rows="3" required
                                class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ $faq->answer }}</textarea>
                            <div class="flex gap-2">
                                <button type="submit" class="flex-1 py-1.5 bg-[#00294d] hover:bg-blue-900 text-white font-bold rounded-lg text-xs transition">Guardar</button>
                                <button type="button" onclick="toggleEditarFaq(this, false)" class="flex-1 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg text-xs transition">Cancelar</button>
                            </div>
                        </form>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>

@push('scripts')
<script>
    function toggleEditarFaq(btn, editar) {
        const item = btn.closest('.faq-item');
        item.querySelector('.faq-vista').classList.toggle('hidden', editar);
        item.querySelector('.faq-editar').classList.toggle('hidden', !editar);
    }
</script>
@endpush
@endsection
