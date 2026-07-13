@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    {{-- Header --}}
    <div class="sticky top-0 z-50 header-corporate text-white w-full px-10 shadow-lg border-b border-white/5">
        <div class="w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
            <div class="space-y-1">
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl">Editar Home</h1>
                <p class="text-sm text-slate-300 font-medium">Cambia el carrusel principal y el texto de bienvenida del inicio. Los “Lugares destacados” se gestionan marcándolos en el módulo de Lugares.</p>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 lg:p-8 w-full">
        <div class="bg-white rounded-2xl p-8 card-premium-shadow max-w-5xl mx-auto">

            <form method="POST" action="{{ route('admin.home.update') }}">
                @csrf
                @method('PUT')

                {{-- Bienvenida --}}
                <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2"><i class="fas fa-pen text-[#00913f]"></i> Texto de bienvenida</h2>
                <div class="space-y-4 mb-10">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Título</label>
                        <input type="text" name="welcome_title" value="{{ old('welcome_title', $settings->welcome_title) }}" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00913f] outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Párrafo de presentación</label>
                        <textarea name="welcome_text" rows="4"
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00913f] outline-none text-sm">{{ old('welcome_text', $settings->welcome_text) }}</textarea>
                    </div>
                </div>

                {{-- Carrusel --}}
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2"><i class="fas fa-images text-[#00913f]"></i> Carrusel principal</h2>
                    <button type="button" onclick="agregarSlide()" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-bold uppercase tracking-wider">
                        <i class="fas fa-plus"></i> Agregar diapositiva
                    </button>
                </div>
                <p class="text-xs text-slate-400 mb-4">
                    <i class="fas fa-circle-info text-[#00913f]/60"></i>
                    Estas imágenes se muestran <span class="font-semibold text-slate-500">a pantalla completa al inicio del Home</span>, en el mismo orden que aparecen aquí (de izquierda a derecha). Sube o cambia la imagen de cada diapositiva; el título y subtítulo se ven encima de la foto.
                </p>

                <div id="slides" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-8 items-start"></div>

                {{-- ===== Secciones visibles ===== --}}
                @php $sec = $settings->secciones ?? ['destacados'=>true,'noticias'=>true,'eventos'=>true]; @endphp
                <div class="mb-8 border-t border-slate-100 pt-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2"><i class="fas fa-eye text-[#00913f]"></i> Secciones visibles en el inicio</h2>
                    <div class="flex flex-wrap gap-5">
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" name="sec_destacados" value="1" {{ ($sec['destacados'] ?? true) ? 'checked' : '' }} class="w-5 h-5"> <i class="fas fa-star text-slate-400"></i> Lugares destacados</label>
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" name="sec_noticias" value="1" {{ ($sec['noticias'] ?? true) ? 'checked' : '' }} class="w-5 h-5"> <i class="fas fa-newspaper text-slate-400"></i> Noticias</label>
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" name="sec_eventos" value="1" {{ ($sec['eventos'] ?? true) ? 'checked' : '' }} class="w-5 h-5"> <i class="fas fa-calendar-days text-slate-400"></i> Eventos</label>
                    </div>
                </div>

                {{-- ===== Eventos a mostrar ===== --}}
                @php $eids = $settings->eventos_ids ?? []; @endphp
                <div class="mb-8" data-picker>
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2"><i class="fas fa-calendar text-[#00913f]"></i> ¿Qué eventos mostrar en el inicio?</h2>
                        <span class="picker-count shrink-0 inline-flex items-center gap-1.5 text-xs font-bold text-[#00913f] bg-[#00913f]/10 px-3 py-1 rounded-full">0 seleccionados</span>
                    </div>
                    <p class="text-xs text-slate-400 mb-3">Marca los que quieras destacar. Si no marcas ninguno, se mostrarán los 3 más recientes.</p>

                    <div class="relative mb-3">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                        <input type="text" class="picker-search w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-[#00913f] outline-none" placeholder="Buscar evento…">
                    </div>

                    <div class="picker-list grid sm:grid-cols-2 gap-2 max-h-72 overflow-y-auto border border-slate-100 rounded-xl p-3 bg-slate-50/50">
                        @forelse($todosEventos as $ev)
                            <label class="picker-item group flex items-center gap-3 p-2.5 rounded-lg border border-slate-200 bg-white cursor-pointer transition hover:border-[#eab52a] hover:shadow-sm has-[:checked]:border-[#00913f] has-[:checked]:bg-[#00913f]/5 has-[:checked]:shadow-sm" data-label="{{ \Illuminate\Support\Str::lower($ev->title) }}">
                                <input type="checkbox" name="eventos_ids[]" value="{{ $ev->id }}" {{ in_array($ev->id, $eids) ? 'checked' : '' }} class="peer sr-only">
                                <span class="shrink-0 w-5 h-5 rounded-md border-2 border-slate-300 bg-white flex items-center justify-center peer-checked:bg-[#00913f] peer-checked:border-[#00913f] transition">
                                    <i class="fas fa-check text-[10px] text-white"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-slate-700 truncate">{{ $ev->title }}</span>
                                    @if($ev->starts_at)
                                        <span class="block text-[11px] text-slate-400"><i class="far fa-calendar mr-1"></i>{{ $ev->starts_at->translatedFormat('d M Y') }}</span>
                                    @endif
                                </span>
                            </label>
                        @empty
                            <p class="text-xs text-slate-400 col-span-full py-2">No hay eventos creados todavía.</p>
                        @endforelse
                    </div>
                    <p class="picker-noresults hidden text-xs text-slate-400 mt-2 text-center">Sin resultados para tu búsqueda.</p>
                </div>

                {{-- ===== Noticias a mostrar ===== --}}
                @php $nids = $settings->noticias_ids ?? []; @endphp
                <div class="mb-8" data-picker>
                    <div class="flex items-center justify-between gap-3 mb-1">
                        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2"><i class="fas fa-newspaper text-[#00913f]"></i> ¿Qué noticias mostrar en el inicio?</h2>
                        <span class="picker-count shrink-0 inline-flex items-center gap-1.5 text-xs font-bold text-[#00913f] bg-[#00913f]/10 px-3 py-1 rounded-full">0 seleccionadas</span>
                    </div>
                    <p class="text-xs text-slate-400 mb-3">Marca las que quieras destacar. Si no marcas ninguna, se mostrarán las 3 más recientes.</p>

                    <div class="relative mb-3">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                        <input type="text" class="picker-search w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-[#00913f] outline-none" placeholder="Buscar noticia…">
                    </div>

                    <div class="picker-list grid sm:grid-cols-2 gap-2 max-h-72 overflow-y-auto border border-slate-100 rounded-xl p-3 bg-slate-50/50">
                        @forelse($todasNoticias as $n)
                            <label class="picker-item group flex items-center gap-3 p-2.5 rounded-lg border border-slate-200 bg-white cursor-pointer transition hover:border-[#eab52a] hover:shadow-sm has-[:checked]:border-[#00913f] has-[:checked]:bg-[#00913f]/5 has-[:checked]:shadow-sm" data-label="{{ \Illuminate\Support\Str::lower($n->title) }}">
                                <input type="checkbox" name="noticias_ids[]" value="{{ $n->id }}" {{ in_array($n->id, $nids) ? 'checked' : '' }} class="peer sr-only">
                                <span class="shrink-0 w-5 h-5 rounded-md border-2 border-slate-300 bg-white flex items-center justify-center peer-checked:bg-[#00913f] peer-checked:border-[#00913f] transition">
                                    <i class="fas fa-check text-[10px] text-white"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-slate-700 truncate">{{ $n->title }}</span>
                                    @if($n->published_at)
                                        <span class="block text-[11px] text-slate-400"><i class="far fa-newspaper mr-1"></i>{{ $n->published_at->translatedFormat('d M Y') }}</span>
                                    @endif
                                </span>
                            </label>
                        @empty
                            <p class="text-xs text-slate-400 col-span-full py-2">No hay noticias creadas todavía.</p>
                        @endforelse
                    </div>
                    <p class="picker-noresults hidden text-xs text-slate-400 mt-2 text-center">Sin resultados para tu búsqueda.</p>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-[#00913f] hover:bg-[#04301e] text-white rounded-xl text-xs font-black tracking-wider uppercase shadow-md mt-6">
                        <i class="fas fa-save"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="tpl-slide">
    <div class="slide relative border border-slate-200 rounded-2xl p-4 bg-white shadow-sm">
        {{-- Cabecera: número de posición + quitar --}}
        <div class="flex items-center justify-between mb-3">
            <span class="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-wider text-[#00913f] bg-slate-100 px-3 py-1 rounded-full">
                <i class="fas fa-clone text-[10px]"></i> Diapositiva <span class="slide-pos">1</span>
            </span>
            <button type="button" class="remove-btn text-red-500 hover:text-red-700 text-xs font-bold flex items-center gap-1"><i class="fas fa-trash-alt"></i> Quitar</button>
        </div>

        <div class="flex flex-col gap-4">
            {{-- Imagen grande + subir --}}
            <div class="shrink-0">
                <div class="preview relative w-full h-44 rounded-xl bg-slate-100 border-2 border-dashed border-slate-200 overflow-hidden flex flex-col items-center justify-center gap-1 text-slate-400">
                    <i class="fas fa-image text-3xl"></i>
                    <span class="text-[11px] font-semibold">Sin imagen</span>
                </div>
                <input type="hidden" class="url-input" name="">
                <label class="mt-2 block">
                    <span class="inline-flex w-full items-center justify-center gap-1.5 px-3 py-2 bg-[#00913f] hover:bg-[#04301e] text-white rounded-lg text-xs font-bold cursor-pointer transition">
                        <i class="fas fa-upload text-[10px]"></i> Subir / cambiar imagen
                    </span>
                    <input type="file" accept="image/*" class="file-input hidden">
                </label>
            </div>

            {{-- Textos que se ven encima de la imagen --}}
            <div class="flex-1 space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Título (se ve grande sobre la imagen)</label>
                    <input type="text" class="title-input w-full px-3 py-2 rounded-lg border border-slate-200 text-sm" placeholder="Ej. San José de Chimbo">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Subtítulo (opcional)</label>
                    <input type="text" class="subtitle-input w-full px-3 py-2 rounded-lg border border-slate-200 text-sm" placeholder="Ej. Naturaleza, cultura y aventura en los Andes">
                </div>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
    const CSRF = '{{ csrf_token() }}';
    const UPLOAD_URL = '{{ route('admin.home.upload') }}';
    let idx = 0;

    function nuevaSlide(data = {}) {
        const tpl = document.getElementById('tpl-slide').content.cloneNode(true);
        const slide = tpl.querySelector('.slide');
        const i = idx++;

        const urlInput = slide.querySelector('.url-input');
        const titleInput = slide.querySelector('.title-input');
        const subInput = slide.querySelector('.subtitle-input');
        const preview = slide.querySelector('.preview');

        urlInput.name = `carousel[${i}][url]`;
        titleInput.name = `carousel[${i}][title]`;
        subInput.name = `carousel[${i}][subtitle]`;

        urlInput.value = data.url || '';
        titleInput.value = data.title || '';
        subInput.value = data.subtitle || '';
        if (data.url) { preview.classList.remove('border-dashed'); preview.innerHTML = `<img src="{{ url('/') }}${data.url.startsWith('/') ? '' : '/'}${data.url}" class="w-full h-full object-cover">`; }

        slide.querySelector('.file-input').addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('file', file);
            preview.innerHTML = '<i class="fas fa-spinner fa-spin text-slate-400"></i>';
            try {
                const resp = await fetch(UPLOAD_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd });
                const json = await resp.json();
                if (json.url) {
                    urlInput.value = json.url;
                    preview.classList.remove('border-dashed');
                    preview.innerHTML = `<img src="{{ url('/') }}${json.url}" class="w-full h-full object-cover">`;
                } else {
                    preview.innerHTML = '<span class="text-red-400 text-xs">Error</span>';
                }
            } catch (err) {
                preview.innerHTML = '<span class="text-red-400 text-xs">Error</span>';
            }
        });

        slide.querySelector('.remove-btn').addEventListener('click', () => { slide.remove(); renumerar(); });
        document.getElementById('slides').appendChild(slide);
        renumerar();
    }

    // Numera cada diapositiva según su orden actual (1, 2, 3…) para que se vea
    // claramente en qué posición del carrusel aparecerá cada imagen.
    function renumerar() {
        document.querySelectorAll('#slides .slide .slide-pos').forEach((el, i) => { el.textContent = i + 1; });
    }

    function agregarSlide() { nuevaSlide(); }

    // Cargar las diapositivas existentes
    const existentes = @json($settings->carousel ?? []);
    if (existentes.length) {
        existentes.forEach(s => nuevaSlide(s));
    } else {
        nuevaSlide();
    }

    // ===== Selectores de eventos / noticias: contador en vivo + buscador =====
    document.querySelectorAll('[data-picker]').forEach((picker) => {
        const items    = picker.querySelectorAll('.picker-item');
        const countEl  = picker.querySelector('.picker-count');
        const searchEl = picker.querySelector('.picker-search');
        const noRes    = picker.querySelector('.picker-noresults');
        // Base del texto del contador ("seleccionados" / "seleccionadas").
        const palabra  = (countEl?.textContent || '0 seleccionados').replace(/^\d+\s*/, '');

        function actualizarContador() {
            const n = picker.querySelectorAll('.picker-item input:checked').length;
            if (countEl) countEl.textContent = `${n} ${palabra}`;
        }
        picker.addEventListener('change', actualizarContador);
        actualizarContador();

        if (searchEl) {
            searchEl.addEventListener('input', () => {
                const q = searchEl.value.trim().toLowerCase();
                let visibles = 0;
                items.forEach((it) => {
                    const coincide = (it.dataset.label || '').includes(q);
                    it.classList.toggle('hidden', !coincide);
                    if (coincide) visibles++;
                });
                if (noRes) noRes.classList.toggle('hidden', visibles !== 0);
            });
        }
    });
</script>
@endpush
@endsection
