@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    {{-- Header de Pantalla Completa (mismo patrón que el resto del panel) --}}
    <div class="sticky top-0 z-30 bg-[#00294d] text-white w-full px-10 py-8 shadow-lg border-b border-white/5">
        <div class="w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
            <div class="space-y-1">
                <a href="{{ route('admin.noticias.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 hover:text-white transition mb-1">
                    <i class="fas fa-arrow-left text-[10px]"></i> Volver a Noticias
                </a>
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl flex items-center gap-3">
                    <i class="fas fa-pen-to-square text-lg text-slate-300"></i> Editar Noticia
                </h1>
                <p class="text-sm text-slate-300 font-medium">{{ $noticia->title }}</p>
            </div>
        </div>
    </div>

    <div class="p-8 w-full">
        <div class="bg-white rounded-2xl card-premium-shadow max-w-4xl mx-auto">
            <form method="POST" action="{{ route('admin.noticias.update', $noticia->id) }}" id="noticiaForm" class="p-8 sm:p-10 space-y-10">
                @csrf
                @method('PUT')

                {{-- Sección: Información general --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-circle-info"></i> Información general
                    </h2>
                    <div>
                        <label for="title" class="block text-sm font-bold text-slate-700 mb-1.5">Título de la Noticia *</label>
                        <input type="text" name="title" id="title" value="{{ old('title', $noticia->title) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 outline-none text-sm transition" required>
                    </div>
                    <div>
                        <label for="categoria" class="block text-sm font-bold text-slate-700 mb-1.5">Categoría</label>
                        <input type="text" name="categoria" id="categoria" list="cats-noticias" value="{{ old('categoria', $noticia->categoria) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 outline-none text-sm transition"
                               placeholder="Ej: Política, Cultura, Deportes...">
                        <datalist id="cats-noticias">
                            <option value="Política"><option value="Cultura"><option value="Deportes"><option value="Comunidad"><option value="Turismo"><option value="Economía">
                        </datalist>
                    </div>
                    <div>
                        <label for="published_at" class="block text-sm font-bold text-slate-700 mb-1.5">Fecha de Publicación</label>
                        <input type="date" name="published_at" id="published_at"
                               value="{{ old('published_at', $noticia->published_at ? \Carbon\Carbon::parse($noticia->published_at)->format('Y-m-d') : '') }}"
                               class="w-full sm:w-64 px-4 py-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 outline-none text-sm transition">
                    </div>
                </section>

                {{-- Sección: Contenido --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-align-left"></i> Contenido
                    </h2>
                    <div>
                        <label for="body" class="block text-sm font-bold text-slate-700 mb-1.5">Contenido de la Noticia *</label>
                        <textarea name="body" id="body" rows="7" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 outline-none text-sm leading-relaxed transition" required>{{ old('body', $noticia->body) }}</textarea>
                    </div>
                </section>

                {{-- Sección: Multimedia --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-camera"></i> Imagen de portada
                    </h2>

                    <div id="dropzoneArea" class="border-2 border-dashed border-emerald-400 rounded-2xl p-8 sm:p-10 text-center cursor-pointer bg-slate-50/60 hover:bg-emerald-50 transition">
                        <i class="fas fa-cloud-upload-alt text-4xl text-emerald-500 mb-3 block"></i>
                        <p class="text-slate-600 text-sm font-medium">Arrastra una imagen aquí o haz clic para cambiarla</p>
                        <p class="text-xs text-slate-400 mt-1">JPG, PNG, GIF (máx. 2MB)</p>
                        <input type="file" id="fileInput" accept="image/*" style="display: none;">
                    </div>

                    {{-- Preview e Input Oculto --}}
                    <div id="previewContainer" class="{{ $noticia->image_url ? '' : 'hidden' }} max-w-xs relative group border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <img id="imagePreview" src="{{ $noticia->image_url ? url($noticia->image_url) : '#' }}" class="w-full h-44 object-cover">
                        <button type="button" id="btnRemoveImage" class="absolute top-2 right-2 bg-rose-500 text-white rounded-full w-7 h-7 text-xs flex items-center justify-center opacity-90 group-hover:opacity-100 hover:bg-rose-600 transition shadow"><i class="fas fa-xmark"></i></button>
                    </div>

                    <input type="hidden" name="image_url" id="image_url" value="{{ old('image_url', $noticia->image_url) }}">
                </section>

                {{-- Sección: Galería adicional --}}
                <section class="space-y-5">
                    @include('admin.partials.galeria-uploader', ['uploadRoute' => 'admin.noticias.upload', 'existing' => $noticia->images ?? []])
                </section>

                {{-- Acciones --}}
                <div class="flex items-center gap-3 pt-6 border-t border-slate-100">
                    <button type="submit" id="submitBtn" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm transition-all shadow-md inline-flex items-center gap-2">
                        <i class="fas fa-floppy-disk"></i> Guardar Cambios
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
    const imagePreview     = document.getElementById('imagePreview');
    const imageUrlInput    = document.getElementById('image_url');
    const btnRemoveImage   = document.getElementById('btnRemoveImage');
    const submitBtn        = document.getElementById('submitBtn');

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
        if (files.length === 0) return;
        const file = files[0];

        if (!file.type.startsWith('image/')) {
            alert('Por favor selecciona una imagen válida');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen no puede superar los 2MB');
            return;
        }

        await subirImagen(file);
    }

    async function subirImagen(file) {
        submitBtn.disabled = true;
        previewContainer.classList.remove('hidden');
        imagePreview.style.opacity = '0.4';
        
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
                const fullUrl = data.url.startsWith('http') ? data.url : '{{ url("/") }}' + data.url;
                imagePreview.src = fullUrl;
                imageUrlInput.value = data.url;
            } else {
                alert('Error al subir la imagen');
                limpiarCampoImagen();
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error en la conexión con el servidor.');
            limpiarCampoImagen();
        } finally {
            imagePreview.style.opacity = '1';
            submitBtn.disabled = false;
        }
    }

    btnRemoveImage.addEventListener('click', (e) => {
        e.preventDefault();
        confirmarAccion('¿Deseas quitar la imagen de esta noticia?', limpiarCampoImagen, {
            titulo: 'Quitar imagen', boton: 'Quitar', icono: 'fa-trash-alt'
        });
    });

    function limpiarCampoImagen() {
        previewContainer.classList.add('hidden');
        imagePreview.src = '#';
        imageUrlInput.value = '';
        fileInput.value = '';
    }
</script>
@endpush
@endsection