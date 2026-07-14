{{--
    Uploader de galería reutilizable (portada + galería).
    Variables esperadas:
      $uploadRoute : nombre de la ruta de subida (ej. 'admin.noticias.upload')
      $existing    : array de URLs ya guardadas (opcional, para edición)
--}}
@php
    $existing = $existing ?? [];
    $field    = $field ?? 'images';
    $titulo   = $titulo ?? '<i class="fas fa-images text-slate-400"></i> Galería (fotos y videos adicionales)';
    $ayuda    = $ayuda ?? 'Estas se mostrarán como galería dentro del detalle. La portada es la imagen de arriba.';
    $extVideo = ['mp4', 'mov', 'webm'];
@endphp

<div>
    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 mb-4 border-b border-slate-100">{!! $titulo !!}</h2>
    <p class="text-sm text-slate-500 -mt-2 mb-4">{{ $ayuda }}</p>

    <label class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-green-300 rounded-xl text-sm font-bold text-green-600 cursor-pointer hover:bg-green-50 transition">
        <i class="fas fa-plus"></i> Agregar fotos o videos
        <input type="file" id="galeriaInput" accept="image/*,video/*" multiple class="hidden">
    </label>

    <div id="galeriaPreviews" class="flex flex-wrap gap-3 mt-4">
        @foreach($existing as $url)
            @php
                $u = is_array($url) ? ($url['url'] ?? '') : $url;
                $esVideo = in_array(strtolower(pathinfo($u, PATHINFO_EXTENSION)), $extVideo);
                $src = \Illuminate\Support\Str::startsWith($u, 'http') ? $u : url('/') . $u;
            @endphp
            <div class="galeria-item relative w-24 h-24 rounded-lg overflow-hidden border border-gray-200">
                @if($esVideo)
                    <video src="{{ $src }}" class="w-full h-full object-cover" muted></video>
                    <span class="absolute bottom-1 left-1 bg-black/60 text-white text-[9px] px-1 rounded"><i class="fas fa-play"></i> video</span>
                @else
                    <img src="{{ $src }}" class="w-full h-full object-cover">
                @endif
                <input type="hidden" name="{{ $field }}[]" value="{{ $u }}">
                <button type="button" class="galeria-remove absolute top-1 right-1 bg-red-500 text-white w-5 h-5 rounded-full text-xs leading-none"><i class="fas fa-xmark"></i></button>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
(function () {
    const input    = document.getElementById('galeriaInput');
    const previews = document.getElementById('galeriaPreviews');
    const UPLOAD   = '{{ route($uploadRoute) }}';
    const CSRF     = '{{ csrf_token() }}';
    const BASE     = '{{ url('/') }}';
    const FIELD    = '{{ $field }}';

    function addItem(url, tipo) {
        const src = url.startsWith('http') ? url : BASE + url;
        const div = document.createElement('div');
        div.className = 'galeria-item relative w-24 h-24 rounded-lg overflow-hidden border border-gray-200';
        const media = tipo === 'video'
            ? `<video src="${src}" class="w-full h-full object-cover" muted></video><span class="absolute bottom-1 left-1 bg-black/60 text-white text-[9px] px-1 rounded"><i class="fas fa-play"></i> video</span>`
            : `<img src="${src}" class="w-full h-full object-cover">`;
        div.innerHTML = `
            ${media}
            <input type="hidden" name="${FIELD}[]" value="${url}">
            <button type="button" class="galeria-remove absolute top-1 right-1 bg-red-500 text-white w-5 h-5 rounded-full text-xs leading-none"><i class="fas fa-xmark"></i></button>`;
        previews.appendChild(div);
    }

    async function subir(file) {
        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', CSRF);
        const placeholder = document.createElement('div');
        placeholder.className = 'w-24 h-24 rounded-lg border border-gray-200 flex items-center justify-center text-gray-300';
        placeholder.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        previews.appendChild(placeholder);
        try {
            const resp = await fetch(UPLOAD, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd });
            const data = await resp.json();
            placeholder.remove();
            if (data.url) addItem(data.url, data.type);
            else alert('Error al subir: ' + (data.error || ''));
        } catch (e) {
            placeholder.remove();
            alert('Error al subir el archivo.');
        }
    }

    input.addEventListener('change', e => {
        [...e.target.files].forEach(f => {
            if (f.type.startsWith('image/') || f.type.startsWith('video/')) subir(f);
        });
        input.value = '';
    });

    previews.addEventListener('click', e => {
        // closest() (no classList.contains sobre e.target) porque el clic casi
        // siempre cae sobre el ícono <i> DENTRO del botón, no sobre el <button>
        // en sí: con classList.contains(e.target) el ícono nunca tiene la clase
        // "galeria-remove" y el borrado nunca se disparaba.
        const boton = e.target.closest('.galeria-remove');
        if (boton) {
            boton.closest('.galeria-item').remove();
        }
    });

    // Se expone subir() para que la página pueda mandar aquí las imágenes
    // pegadas con Ctrl+V cuando la portada ya está ocupada.
    window.galeriaSubirArchivo = subir;
})();
</script>
@endpush
