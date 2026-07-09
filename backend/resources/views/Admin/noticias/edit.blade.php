@extends('admin.layouts.app')

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-xl font-bold mb-4 flex items-center gap-2"><i class="fas fa-pen-to-square text-slate-400"></i> Editar Noticia: <span class="text-green-600">{{ $noticia->title }}</span></h2>

    <form method="POST" action="{{ route('admin.noticias.update', $noticia->id) }}" id="noticiaForm">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Título de la Noticia *</label>
                <input type="text" name="title" id="title" value="{{ old('title', $noticia->title) }}" class="w-full p-2 border rounded" required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Categoría</label>
                <input type="text" name="categoria" list="cats-noticias" value="{{ old('categoria', $noticia->categoria) }}" class="w-full p-2 border rounded" placeholder="Ej: Política, Cultura, Deportes...">
                <datalist id="cats-noticias">
                    <option value="Política"><option value="Cultura"><option value="Deportes"><option value="Comunidad"><option value="Turismo"><option value="Economía">
                </datalist>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Fecha de Publicación</label>
                <input type="date" name="published_at" id="published_at" 
                       value="{{ old('published_at', $noticia->published_at ? \Carbon\Carbon::parse($noticia->published_at)->format('Y-m-d') : '') }}" 
                       class="w-full p-2 border rounded">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Contenido de la Noticia *</label>
                <textarea name="body" id="body" rows="6" class="w-full p-2 border rounded" required>{{ old('body', $noticia->body) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 flex items-center gap-1.5"><i class="fas fa-camera text-slate-400"></i> Imagen de la Noticia</label>

                <div id="dropzoneArea" class="border-2 border-dashed border-green-500 rounded-xl p-6 text-center cursor-pointer bg-gray-50 hover:bg-green-50 transition">
                    <i class="fas fa-cloud-upload-alt text-4xl text-green-500 mb-2 block"></i>
                    <p class="text-gray-600">Arrastra una imagen aquí o haz clic para cambiarla</p>
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF (máx. 2MB)</p>
                    <input type="file" id="fileInput" accept="image/*" style="display: none;">
                </div>

                {{-- Preview e Input Oculto --}}
                <div id="previewContainer" class="mt-4 {{ $noticia->image_url ? '' : 'hidden' }} max-w-xs relative group border rounded-lg overflow-hidden shadow-sm">
                    <img id="imagePreview" src="{{ $noticia->image_url ? url($noticia->image_url) : '#' }}" class="w-full h-44 object-cover">
                    <button type="button" id="btnRemoveImage" class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center opacity-80 group-hover:opacity-100 transition shadow"><i class="fas fa-xmark"></i></button>
                </div>

                <input type="hidden" name="image_url" id="image_url" value="{{ old('image_url', $noticia->image_url) }}">
            </div>

            @include('admin.partials.galeria-uploader', ['uploadRoute' => 'admin.noticias.upload', 'existing' => $noticia->images ?? []])
        </div>

        <div class="mt-6 border-t pt-4">
            <button type="submit" id="submitBtn" class="px-5 py-2.5 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition shadow-sm flex items-center gap-2 inline-flex">
                <i class="fas fa-floppy-disk"></i> Guardar Cambios
            </button>
            <a href="{{ route('admin.noticias.index') }}" class="px-5 py-2.5 bg-gray-500 text-white font-medium rounded-lg hover:bg-gray-600 transition ml-2">
                Cancelar
            </a>
        </div>
    </form>
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
        if(confirm('¿Deseas quitar la imagen de esta noticia?')) {
            limpiarCampoImagen();
        }
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