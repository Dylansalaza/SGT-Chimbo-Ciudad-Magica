@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    {{-- Header de Pantalla Completa (mismo patrón que el resto del panel) --}}
    <div class="sticky top-0 z-30 header-corporate text-white w-full px-10 shadow-lg border-b border-white/5">
        <div class="w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
            <div class="space-y-1">
                <a href="{{ route('admin.noticias.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 hover:text-white transition mb-1">
                    <i class="fas fa-arrow-left text-[10px]"></i> Volver a Noticias
                </a>
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl flex items-center gap-3">
                    <i class="fas fa-newspaper text-lg text-slate-300"></i> Crear Nueva Noticia
                </h1>
                <p class="text-sm text-slate-300 font-medium">Publica una novedad en el boletín informativo del cantón.</p>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 lg:p-8 w-full">
        <div class="bg-white rounded-2xl card-premium-shadow max-w-4xl mx-auto">
            <form method="POST" action="{{ route('admin.noticias.store') }}" id="noticiaForm" class="p-8 sm:p-10 space-y-10">
                @csrf

                {{-- Sección: Información general --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-circle-info"></i> Información general
                    </h2>
                    <div>
                        <label for="title" class="block text-sm font-bold text-slate-700 mb-1.5">Título *</label>
                        <input type="text" name="title" id="title" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-green-600 focus:ring-2 focus:ring-green-600/20 outline-none text-sm transition" required>
                    </div>
                    <div>
                        <label for="categoria" class="block text-sm font-bold text-slate-700 mb-1.5">Categoría</label>
                        @include('admin.partials.categoria-select', ['categorias' => $categorias])
                    </div>
                </section>

                {{-- Sección: Contenido --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-align-left"></i> Contenido
                    </h2>
                    <div>
                        <label for="body" class="block text-sm font-bold text-slate-700 mb-1.5">Contenido *</label>
                        <textarea name="body" id="body" rows="7" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-green-600 focus:ring-2 focus:ring-green-600/20 outline-none text-sm leading-relaxed transition" required></textarea>
                    </div>
                </section>

                {{-- Sección: Publicación --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-calendar-days"></i> Fecha y hora de publicación
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="published_date" class="block text-sm font-bold text-slate-700 mb-1.5">Fecha</label>
                            <input type="date" name="published_date" id="published_date" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-green-600 focus:ring-2 focus:ring-green-600/20 outline-none text-sm transition">
                        </div>
                        <div>
                            <label for="published_time" class="block text-sm font-bold text-slate-700 mb-1.5">Hora</label>
                            <input type="time" name="published_time" id="published_time" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-green-600 focus:ring-2 focus:ring-green-600/20 outline-none text-sm transition">
                        </div>
                    </div>
                </section>

                {{-- Sección: Multimedia --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-photo-film"></i> Portada (imagen o video)
                    </h2>

                    <div id="dropzoneArea" class="border-2 border-dashed border-green-400 rounded-2xl p-8 sm:p-10 text-center cursor-pointer bg-slate-50/60 hover:bg-green-50 transition">
                        <i class="fas fa-cloud-upload-alt text-4xl text-green-500 mb-3 block"></i>
                        <p class="text-slate-600 text-sm font-medium">Arrastra una imagen o video aquí o haz clic para seleccionar</p>
                        <p class="text-xs text-slate-400 mt-1">JPG, PNG, GIF, WebP o video MP4/WebM/MOV (máx. 40MB)</p>
                        <input type="file" id="fileInput" accept="image/*,video/*" style="display: none;">
                    </div>

                    <div id="previewContainer" class="hidden">
                        <img id="previewImg" class="hidden w-32 h-32 object-cover rounded-xl shadow border border-slate-200">
                        <video id="previewVideo" class="hidden w-48 rounded-xl shadow border border-slate-200" controls muted></video>
                        <p id="previewUrl" class="text-xs text-green-600 mt-2"></p>
                        <button type="button" id="removeImageBtn" class="mt-1 text-red-500 text-xs font-bold hover:underline flex items-center gap-1">
                            <i class="fas fa-trash-alt"></i> Eliminar archivo
                        </button>
                    </div>

                    <input type="hidden" name="image_url" id="imageUrl">
                </section>

                {{-- Sección: Galería adicional --}}
                <section class="space-y-5">
                    @include('admin.partials.galeria-uploader', ['uploadRoute' => 'admin.noticias.upload'])
                </section>

                {{-- Acciones --}}
                <div class="flex items-center gap-3 pt-6 border-t border-slate-100">
                    <button type="submit" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl text-sm transition-all shadow-md inline-flex items-center gap-2">
                        <i class="fas fa-paper-plane"></i> Publicar Noticia
                    </button>
                    <a href="{{ route('admin.noticias.index') }}" class="px-6 py-2.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold rounded-xl text-sm transition-all">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const dropzoneArea     = document.getElementById('dropzoneArea');
    const fileInput        = document.getElementById('fileInput');
    const previewContainer = document.getElementById('previewContainer');
    const previewImg       = document.getElementById('previewImg');
    const previewVideo     = document.getElementById('previewVideo');
    const previewUrl       = document.getElementById('previewUrl');
    const imageUrlInput    = document.getElementById('imageUrl');
    const removeImageBtn   = document.getElementById('removeImageBtn');

    const MAX_MB = 40;
    const esVideoUrl = (u) => /\.(mp4|webm|ogg|mov|m4v)(\?|$)/i.test(u || '');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(event => {
        dropzoneArea.addEventListener(event, e => { e.preventDefault(); e.stopPropagation(); });
    });

    dropzoneArea.addEventListener('dragenter', () => dropzoneArea.classList.add('bg-green-100'));
    dropzoneArea.addEventListener('dragleave', () => dropzoneArea.classList.remove('bg-green-100'));
    dropzoneArea.addEventListener('drop',      () => dropzoneArea.classList.remove('bg-green-100'));

    dropzoneArea.addEventListener('drop',  e  => handleFiles(e.dataTransfer.files));
    dropzoneArea.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change',   e  => handleFiles(e.target.files));

    async function handleFiles(files) {
        if (!files.length) return;

        const file = files[0];

        if (!file.type.startsWith('image/') && !file.type.startsWith('video/')) {
            alert('Solo se permiten imágenes o videos');
            return;
        }
        if (file.size > MAX_MB * 1024 * 1024) {
            alert('El archivo no puede superar los ' + MAX_MB + 'MB');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', '{{ csrf_token() }}');

        try {
            const response = await fetch('{{ route("admin.noticias.upload") }}', {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (data.url) {
                imageUrlInput.value = data.url;
                mostrarPreview(data.url);
                previewContainer.classList.remove('hidden');
                dropzoneArea.style.display = 'none';
            } else {
                alert('Error: ' + (data.error || 'No se recibió la URL'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al subir el archivo: ' + error.message);
        }

        fileInput.value = '';
    }

    // Muestra la vista previa como <video> o <img> según la extensión de la URL.
    function mostrarPreview(url) {
        const full = '{{ url("/") }}' + url;
        if (esVideoUrl(url)) {
            previewVideo.src = full;
            previewVideo.classList.remove('hidden');
            previewImg.classList.add('hidden');
            previewImg.src = '';
            previewUrl.textContent = 'Video subido: ' + url;
        } else {
            previewImg.src = full;
            previewImg.classList.remove('hidden');
            previewVideo.classList.add('hidden');
            previewVideo.src = '';
            previewUrl.textContent = 'Imagen subida: ' + url;
        }
    }

    removeImageBtn.addEventListener('click', () => {
        imageUrlInput.value = '';
        previewImg.src      = '';
        previewVideo.src    = '';
        previewContainer.classList.add('hidden');
        dropzoneArea.style.display = 'block';
    });
</script>
@endpush
@endsection