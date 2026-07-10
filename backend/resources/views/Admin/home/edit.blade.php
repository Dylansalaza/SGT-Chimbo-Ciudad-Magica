@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    {{-- Header --}}
    <div class="sticky top-0 z-50 bg-[#00294d] text-white w-full px-10 py-8 shadow-lg border-b border-white/5">
        <div class="w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
            <div class="space-y-1">
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl">Editar Home</h1>
                <p class="text-sm text-slate-300 font-medium">Cambia el carrusel principal y el texto de bienvenida del inicio. Los “Lugares destacados” se gestionan marcándolos en el módulo de Lugares.</p>
            </div>
        </div>
    </div>

    <div class="p-8 w-full">
        <div class="bg-white rounded-2xl p-8 card-premium-shadow max-w-5xl mx-auto">

            <form method="POST" action="{{ route('admin.home.update') }}">
                @csrf
                @method('PUT')

                {{-- Bienvenida --}}
                <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2"><i class="fas fa-pen text-[#00294d]"></i> Texto de bienvenida</h2>
                <div class="space-y-4 mb-10">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Título</label>
                        <input type="text" name="welcome_title" value="{{ old('welcome_title', $settings->welcome_title) }}" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00294d] outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Párrafo de presentación</label>
                        <textarea name="welcome_text" rows="4"
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00294d] outline-none text-sm">{{ old('welcome_text', $settings->welcome_text) }}</textarea>
                    </div>
                </div>

                {{-- Carrusel --}}
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2"><i class="fas fa-images text-[#00294d]"></i> Carrusel principal</h2>
                    <button type="button" onclick="agregarSlide()" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold uppercase tracking-wider">
                        <i class="fas fa-plus"></i> Agregar diapositiva
                    </button>
                </div>
                <p class="text-xs text-slate-400 mb-4">
                    <i class="fas fa-circle-info text-[#00294d]/60"></i>
                    Estas imágenes se muestran <span class="font-semibold text-slate-500">a pantalla completa al inicio del Home</span>, en el mismo orden que aparecen aquí (de arriba hacia abajo). Sube o cambia la imagen de cada diapositiva; el título y subtítulo se ven encima de la foto.
                </p>

                <div id="slides" class="space-y-4 mb-8"></div>

                {{-- ===== Imágenes ya subidas (historial) ===== --}}
                <div class="mb-10 border-t border-slate-100 pt-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-1 flex items-center gap-2"><i class="fas fa-photo-film text-[#00294d]"></i> Imágenes ya subidas</h2>
                    <p class="text-xs text-slate-400 mb-3">Historial de todas las imágenes subidas al carrusel alguna vez. Reutilízalas en una nueva diapositiva o bórralas definitivamente del servidor.</p>
                    <div id="imagenes-subidas" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3 max-h-72 overflow-y-auto border border-slate-100 rounded-xl p-3">
                        @forelse($imagenesSubidas as $img)
                            <div class="imagen-subida group relative rounded-lg overflow-hidden border border-slate-200" data-nombre="{{ $img['nombre'] }}" data-url="{{ $img['url'] }}">
                                <img src="{{ url('/') }}{{ $img['url'] }}" class="w-full h-20 object-cover">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-1.5">
                                    <button type="button" class="usar-btn text-[10px] font-bold uppercase bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1 rounded">
                                        <i class="fas fa-plus"></i> Usar
                                    </button>
                                    <button type="button" class="borrar-btn text-[10px] font-bold uppercase bg-rose-600 hover:bg-rose-700 text-white px-2 py-1 rounded">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 col-span-full">Todavía no se ha subido ninguna imagen al carrusel.</p>
                        @endforelse
                    </div>
                </div>

                {{-- ===== Secciones visibles ===== --}}
                @php $sec = $settings->secciones ?? ['destacados'=>true,'noticias'=>true,'eventos'=>true]; @endphp
                <div class="mb-8 border-t border-slate-100 pt-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2"><i class="fas fa-eye text-[#00294d]"></i> Secciones visibles en el inicio</h2>
                    <div class="flex flex-wrap gap-5">
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" name="sec_destacados" value="1" {{ ($sec['destacados'] ?? true) ? 'checked' : '' }} class="w-5 h-5"> <i class="fas fa-star text-slate-400"></i> Lugares destacados</label>
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" name="sec_noticias" value="1" {{ ($sec['noticias'] ?? true) ? 'checked' : '' }} class="w-5 h-5"> <i class="fas fa-newspaper text-slate-400"></i> Noticias</label>
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" name="sec_eventos" value="1" {{ ($sec['eventos'] ?? true) ? 'checked' : '' }} class="w-5 h-5"> <i class="fas fa-calendar-days text-slate-400"></i> Eventos</label>
                    </div>
                </div>

                {{-- ===== Eventos a mostrar ===== --}}
                @php $eids = $settings->eventos_ids ?? []; @endphp
                <div class="mb-8">
                    <h2 class="text-lg font-bold text-slate-800 mb-1 flex items-center gap-2"><i class="fas fa-calendar text-[#00294d]"></i> ¿Qué eventos mostrar en el inicio?</h2>
                    <p class="text-xs text-slate-400 mb-3">Marca los que quieras destacar. Si no marcas ninguno, se mostrarán los 3 más recientes.</p>
                    <div class="grid sm:grid-cols-2 gap-1 max-h-60 overflow-y-auto border border-slate-100 rounded-xl p-3">
                        @forelse($todosEventos as $ev)
                            <label class="flex items-center gap-2 text-sm p-1.5 rounded hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="eventos_ids[]" value="{{ $ev->id }}" {{ in_array($ev->id, $eids) ? 'checked' : '' }} class="w-4 h-4">
                                <span class="truncate">{{ $ev->title }}</span>
                            </label>
                        @empty
                            <p class="text-xs text-slate-400">No hay eventos creados todavía.</p>
                        @endforelse
                    </div>
                </div>

                {{-- ===== Noticias a mostrar ===== --}}
                @php $nids = $settings->noticias_ids ?? []; @endphp
                <div class="mb-8">
                    <h2 class="text-lg font-bold text-slate-800 mb-1 flex items-center gap-2"><i class="fas fa-newspaper text-[#00294d]"></i> ¿Qué noticias mostrar en el inicio?</h2>
                    <p class="text-xs text-slate-400 mb-3">Marca las que quieras destacar. Si no marcas ninguna, se mostrarán las 3 más recientes.</p>
                    <div class="grid sm:grid-cols-2 gap-1 max-h-60 overflow-y-auto border border-slate-100 rounded-xl p-3">
                        @forelse($todasNoticias as $n)
                            <label class="flex items-center gap-2 text-sm p-1.5 rounded hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="noticias_ids[]" value="{{ $n->id }}" {{ in_array($n->id, $nids) ? 'checked' : '' }} class="w-4 h-4">
                                <span class="truncate">{{ $n->title }}</span>
                            </label>
                        @empty
                            <p class="text-xs text-slate-400">No hay noticias creadas todavía.</p>
                        @endforelse
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-[#00294d] hover:bg-[#001d38] text-white rounded-xl text-xs font-black tracking-wider uppercase shadow-md mt-6">
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
            <span class="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-wider text-[#00294d] bg-slate-100 px-3 py-1 rounded-full">
                <i class="fas fa-clone text-[10px]"></i> Diapositiva <span class="slide-pos">1</span>
            </span>
            <button type="button" class="remove-btn text-rose-500 hover:text-rose-700 text-xs font-bold flex items-center gap-1"><i class="fas fa-trash-alt"></i> Quitar</button>
        </div>

        <div class="flex flex-col sm:flex-row gap-4">
            {{-- Imagen grande + subir --}}
            <div class="sm:w-72 shrink-0">
                <div class="preview relative w-full h-44 rounded-xl bg-slate-100 border-2 border-dashed border-slate-200 overflow-hidden flex flex-col items-center justify-center gap-1 text-slate-400">
                    <i class="fas fa-image text-3xl"></i>
                    <span class="text-[11px] font-semibold">Sin imagen</span>
                </div>
                <input type="hidden" class="url-input" name="">
                <label class="mt-2 block">
                    <span class="inline-flex w-full items-center justify-center gap-1.5 px-3 py-2 bg-[#00294d] hover:bg-[#001d38] text-white rounded-lg text-xs font-bold cursor-pointer transition">
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
                    preview.innerHTML = '<span class="text-rose-400 text-xs">Error</span>';
                }
            } catch (err) {
                preview.innerHTML = '<span class="text-rose-400 text-xs">Error</span>';
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

    // ===== Galería de imágenes ya subidas (usar / borrar del servidor) =====
    document.getElementById('imagenes-subidas').addEventListener('click', async (e) => {
        const card = e.target.closest('.imagen-subida');
        if (!card) return;
        const url = card.dataset.url;
        const nombre = card.dataset.nombre;

        if (e.target.closest('.usar-btn')) {
            nuevaSlide({ url });
            return;
        }

        if (e.target.closest('.borrar-btn')) {
            confirmarAccion('¿Borrar esta imagen definitivamente del servidor? Esta acción no se puede deshacer.', () => borrarImagenDelServidor(card, nombre), {
                titulo: 'Borrar imagen', boton: 'Borrar', icono: 'fa-trash-alt'
            });
        }
    });

    async function borrarImagenDelServidor(card, nombre) {
        try {
            const resp = await fetch(`{{ url('/admin/home/images') }}/${encodeURIComponent(nombre)}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const json = await resp.json();
            if (resp.ok) {
                card.remove();
            } else {
                alert(json.error || 'No se pudo borrar la imagen.');
            }
        } catch (err) {
            alert('Error de red al intentar borrar la imagen.');
        }
    }
</script>
@endpush
@endsection
