@extends('admin.layouts.app')

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-xl font-bold mb-4 flex items-center gap-2"><i class="fas fa-newspaper text-slate-400"></i> Crear Nueva Noticia</h2>

    <form method="POST" action="{{ route('admin.noticias.store') }}" id="noticiaForm">
        @csrf

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Título *</label>
                <input type="text" name="title" id="title" class="w-full p-2 border rounded" required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Categoría</label>
                <input type="text" name="categoria" list="cats-noticias" class="w-full p-2 border rounded" placeholder="Ej: Política, Cultura, Deportes...">
                <datalist id="cats-noticias">
                    <option value="Política"><option value="Cultura"><option value="Deportes"><option value="Comunidad"><option value="Turismo"><option value="Economía">
                </datalist>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Contenido *</label>
                <textarea name="body" id="body" rows="6" class="w-full p-2 border rounded" required></textarea>
            </div>

            {{-- ⏱️ Bloque Separado de Fecha y Hora de Publicación --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Fecha de publicación</label>
                    <input type="date" name="published_date" id="published_date" class="w-full p-2 border rounded bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Hora de publicación</label>
                    <input type="time" name="published_time" id="published_time" class="w-full p-2 border rounded bg-white">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 flex items-center gap-1.5"><i class="fas fa-camera text-slate-400"></i> Imagen de la noticia</label>

                <div id="dropzoneArea" class="border-2 border-dashed border-green-500 rounded-xl p-8 text-center cursor-pointer bg-gray-50 hover:bg-green-50 transition">
                    <i class="fas fa-cloud-upload-alt text-4xl text-green-500 mb-2 block"></i>
                    <p class="text-gray-600">Arrastra una imagen aquí o haz clic para seleccionar</p>
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF (máx. 2MB)</p>
                    <input type="file" id="fileInput" accept="image/*" style="display: none;">
                </div>

                <div id="previewContainer" class="mt-3 hidden">
                    <img id="previewImg" class="w-32 h-32 object-cover rounded shadow">
                    <p id="previewUrl" class="text-xs text-green-600 mt-1"></p>
                    <button type="button" id="removeImageBtn" class="mt-1 text-red-500 text-sm hover:underline">
                        Eliminar imagen
                    </button>
                </div>

                <input type="hidden" name="image_url" id="imageUrl">
            </div>

            @include('admin.partials.galeria-uploader', ['uploadRoute' => 'admin.noticias.upload'])
        </div>

        <div class="mt-6 border-t pt-4">
            <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 font-semibold text-sm">
                Publicar Noticia
            </button>
            <a href="{{ route('admin.noticias.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 ml-2 text-sm">
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
    const previewImg       = document.getElementById('previewImg');
    const previewUrl       = document.getElementById('previewUrl');
    const imageUrlInput    = document.getElementById('imageUrl');
    const removeImageBtn   = document.getElementById('removeImageBtn');

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

        if (!file.type.startsWith('image/')) {
            alert('Solo se permiten archivos de imagen');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen no puede superar los 2MB');
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
                previewImg.src = '{{ url("/") }}' + data.url;
                previewUrl.textContent = 'Imagen subida: ' + data.url;
                previewContainer.classList.remove('hidden');
                dropzoneArea.style.display = 'none';
            } else {
                alert('Error: ' + (data.error || 'No se recibió la URL'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al subir la imagen: ' + error.message);
        }

        fileInput.value = '';
    }

    removeImageBtn.addEventListener('click', () => {
        imageUrlInput.value = '';
        previewImg.src      = '';
        previewContainer.classList.add('hidden');
        dropzoneArea.style.display = 'block';
    });
</script>
@endpush
@endsection