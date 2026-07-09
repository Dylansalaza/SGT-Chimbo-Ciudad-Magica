@extends('admin.layouts.app')

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-xl font-bold mb-4 flex items-center gap-2"><i class="fas fa-images text-slate-400"></i> Crear Nueva Galería</h2>

    <form method="POST" action="{{ route('admin.galerias.store') }}" id="galeriaForm">
        @csrf

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Título *</label>
                <input type="text" name="title" id="title" class="w-full p-2 border rounded" required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 flex items-center gap-1.5"><i class="fas fa-camera text-slate-400"></i> Imágenes de la galería (puedes subir varias)</label>

                <div id="dropzoneArea" class="border-2 border-dashed border-purple-500 rounded-xl p-8 text-center cursor-pointer bg-gray-50 hover:bg-purple-50 transition">
                    <i class="fas fa-cloud-upload-alt text-4xl text-purple-500 mb-2 block"></i>
                    <p class="text-gray-600">Arrastra imágenes aquí o haz clic para seleccionar</p>
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF (máx. 2MB por imagen)</p>
                    <input type="file" id="fileInput" accept="image/*" multiple style="display: none;">
                </div>

                {{-- Preview de imágenes subidas --}}
                <div id="previewGrid" class="mt-4 grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>

                {{-- Aquí se añaden los inputs hidden dinámicamente --}}
                <div id="imageInputs"></div>

                <p class="text-xs text-gray-400 mt-2">
                    Imágenes agregadas: <span id="imageCount">0</span>
                </p>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" id="submitBtn" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 disabled:opacity-50">
                Crear Galería
            </button>
            <a href="{{ route('admin.galerias.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 ml-2">
                Cancelar
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const dropzoneArea  = document.getElementById('dropzoneArea');
    const fileInput     = document.getElementById('fileInput');
    const previewGrid   = document.getElementById('previewGrid');
    const imageInputs   = document.getElementById('imageInputs');
    const imageCount    = document.getElementById('imageCount');

    let totalImagenes = 0;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(event => {
        dropzoneArea.addEventListener(event, e => { e.preventDefault(); e.stopPropagation(); });
    });

    dropzoneArea.addEventListener('dragenter', () => dropzoneArea.classList.add('bg-purple-100'));
    dropzoneArea.addEventListener('dragleave', () => dropzoneArea.classList.remove('bg-purple-100'));
    dropzoneArea.addEventListener('drop',      () => dropzoneArea.classList.remove('bg-purple-100'));

    dropzoneArea.addEventListener('drop',  e  => handleFiles(e.dataTransfer.files));
    dropzoneArea.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change',   e  => handleFiles(e.target.files));

    async function handleFiles(files) {
        const validFiles = Array.from(files).filter(file => {
            if (!file.type.startsWith('image/')) {
                alert(`"${file.name}" no es una imagen válida`);
                return false;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert(`"${file.name}" supera los 2MB`);
                return false;
            }
            return true;
        });

        for (const file of validFiles) {
            await subirImagen(file);
        }

        fileInput.value = '';
    }

    async function subirImagen(file) {
        // Mostrar placeholder mientras sube
        const idx         = totalImagenes++;
        const placeholder = document.createElement('div');
        placeholder.id    = `placeholder-${idx}`;
        placeholder.className = 'relative w-full h-24 bg-gray-200 rounded-lg flex items-center justify-center';
        placeholder.innerHTML = '<div class="animate-spin rounded-full h-6 w-6 border-b-2 border-purple-500"></div>';
        previewGrid.appendChild(placeholder);

        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', '{{ csrf_token() }}');

        try {
            const response = await fetch('{{ route("admin.galerias.upload") }}', {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (data.url) {
                // ✅ path relativo: /storage/galerias/foto.jpg
                agregarImagenPreview(idx, data.url);
                agregarInputHidden(data.url);
                actualizarContador();
            } else {
                placeholder.remove();
                totalImagenes--;
                alert('Error subiendo: ' + file.name);
            }
        } catch (error) {
            placeholder.remove();
            totalImagenes--;
            console.error('Error:', error);
            alert('Error al subir: ' + file.name);
        }
    }

    function agregarImagenPreview(idx, url) {
        const placeholder = document.getElementById(`placeholder-${idx}`);
        // URL completa solo para previsualizar
        const fullUrl = '{{ url("/") }}' + url;

        placeholder.className = 'relative group';
        placeholder.innerHTML = `
            <img src="${fullUrl}" class="w-full h-24 object-cover rounded-lg shadow">
            <button 
                type="button"
                onclick="eliminarImagen(this, '${url}')"
                class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition"
            ><i class="fas fa-xmark"></i></button>
        `;
    }

    function agregarInputHidden(url) {
        const input    = document.createElement('input');
        input.type     = 'hidden';
        input.name     = 'images[]';
        input.value    = url;
        input.dataset.url = url;
        imageInputs.appendChild(input);
    }

    function eliminarImagen(btn, url) {
        // Eliminar preview
        btn.closest('.relative').remove();

        // Eliminar input hidden correspondiente
        const input = imageInputs.querySelector(`input[data-url="${url}"]`);
        if (input) input.remove();

        actualizarContador();
    }

    function actualizarContador() {
        const total = imageInputs.querySelectorAll('input[type="hidden"]').length;
        imageCount.textContent = total;
    }
</script>
@endpush
@endsection