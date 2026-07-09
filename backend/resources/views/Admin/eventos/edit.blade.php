@extends('admin.layouts.app')

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-xl font-bold mb-4 flex items-center gap-2"><i class="fas fa-pen-to-square text-slate-400"></i> Editar Evento: <span class="text-blue-600">{{ $evento->title }}</span></h2>

    <form method="POST" action="{{ route('admin.eventos.update', $evento->id) }}" id="eventoForm">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Título del Evento *</label>
                <input type="text" name="title" id="title" value="{{ old('title', $evento->title) }}" class="w-full p-2 border rounded" required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Categoría</label>
                <input type="text" name="categoria" list="cats-eventos" value="{{ old('categoria', $evento->categoria) }}" class="w-full p-2 border rounded" placeholder="Ej: Cultural, Religioso, Deportivo...">
                <datalist id="cats-eventos">
                    <option value="Cultural"><option value="Religioso"><option value="Deportivo"><option value="Gastronómico"><option value="Musical"><option value="Feria">
                </datalist>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Fecha de Inicio</label>
                    <input type="datetime-local" name="starts_at" id="starts_at" 
                           value="{{ old('starts_at', $evento->starts_at ? \Carbon\Carbon::parse($evento->starts_at)->format('Y-m-d\TH:i') : '') }}" 
                           class="w-full p-2 border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Fecha de Finalización</label>
                    <input type="datetime-local" name="ends_at" id="ends_at" 
                           value="{{ old('ends_at', $evento->ends_at ? \Carbon\Carbon::parse($evento->ends_at)->format('Y-m-d\TH:i') : '') }}" 
                           class="w-full p-2 border rounded">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Descripción</label>
                <textarea name="description" id="description" rows="4" class="w-full p-2 border rounded">{{ old('description', $evento->description) }}</textarea>
            </div>

            @php
                $esVideoActual = $evento->image_url && \Illuminate\Support\Str::endsWith(strtolower($evento->image_url), ['.mp4', '.mov', '.webm']);
            @endphp
            <div>
                <label class="block text-sm font-medium mb-2 flex items-center gap-1.5"><i class="fas fa-camera text-slate-400"></i> Foto o video de portada del evento</label>

                <div id="dropzoneArea" class="border-2 border-dashed border-blue-500 rounded-xl p-6 text-center cursor-pointer bg-gray-50 hover:bg-blue-50 transition">
                    <i class="fas fa-cloud-upload-alt text-4xl text-blue-500 mb-2 block"></i>
                    <p class="text-gray-600">Arrastra una nueva foto o video aquí, o haz clic para cambiarla</p>
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF, WEBP o MP4/MOV/WEBM (máx. 25MB)</p>
                    <input type="file" id="fileInput" accept="image/*,video/*" style="display: none;">
                </div>

                {{-- Preview e Input Oculto --}}
                <div id="previewContainer" class="mt-4 {{ $evento->image_url ? '' : 'hidden' }} max-w-xs relative group border rounded-lg overflow-hidden shadow-sm">
                    <img id="imagePreview" src="{{ $evento->image_url && !$esVideoActual ? url($evento->image_url) : '#' }}" class="w-full h-44 object-cover {{ $esVideoActual ? 'hidden' : '' }}">
                    <video id="videoPreview" src="{{ $evento->image_url && $esVideoActual ? url($evento->image_url) : '' }}" class="w-full h-44 object-cover {{ $esVideoActual ? '' : 'hidden' }}" controls muted></video>
                    <button type="button" id="btnRemoveImage" class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center opacity-80 group-hover:opacity-100 transition shadow"><i class="fas fa-xmark"></i></button>
                </div>

                <input type="hidden" name="image_url" id="image_url" value="{{ old('image_url', $evento->image_url) }}">
            </div>

            @include('admin.partials.galeria-uploader', ['uploadRoute' => 'admin.eventos.upload', 'existing' => $evento->images ?? []])
        </div>

        <div class="mt-6 border-t pt-4">
            <button type="submit" id="submitBtn" class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm inline-flex items-center gap-2">
                <i class="fas fa-floppy-disk"></i> Guardar Cambios
            </button>
            <a href="{{ route('admin.eventos.index') }}" class="px-5 py-2.5 bg-gray-500 text-white font-medium rounded-lg hover:bg-gray-600 transition ml-2">
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
    const videoPreview     = document.getElementById('videoPreview');
    const imageUrlInput    = document.getElementById('image_url');
    const btnRemoveImage   = document.getElementById('btnRemoveImage');
    const submitBtn        = document.getElementById('submitBtn');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(event => {
        dropzoneArea.addEventListener(event, e => { e.preventDefault(); e.stopPropagation(); });
    });

    dropzoneArea.addEventListener('dragenter', () => dropzoneArea.classList.add('bg-blue-100'));
    dropzoneArea.addEventListener('dragleave', () => dropzoneArea.classList.remove('bg-blue-100'));
    dropzoneArea.addEventListener('drop',      () => dropzoneArea.classList.remove('bg-blue-100'));

    dropzoneArea.addEventListener('drop',  e  => handleFiles(e.dataTransfer.files));
    dropzoneArea.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change',   e  => handleFiles(e.target.files));

    async function handleFiles(files) {
        if (files.length === 0) return;
        const file = files[0];

        const esImagen = file.type.startsWith('image/');
        const esVideo  = file.type.startsWith('video/');
        if (!esImagen && !esVideo) {
            alert('Por favor selecciona una foto, GIF o video válido');
            return;
        }
        if (file.size > 25 * 1024 * 1024) {
            alert('El archivo no puede superar los 25MB');
            return;
        }

        await subirImagen(file);
    }

    async function subirImagen(file) {
        submitBtn.disabled = true;
        previewContainer.classList.remove('hidden');

        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', '{{ csrf_token() }}');

        try {
            const response = await fetch('{{ route("admin.eventos.upload") }}', {
                method: 'POST',
                body: formData,
            });
            const data = await response.json();

            if (data.url) {
                const fullUrl = data.url.startsWith('http') ? data.url : '{{ url("/") }}' + data.url;
                if (data.type === 'video') {
                    videoPreview.src = fullUrl;
                    videoPreview.classList.remove('hidden');
                    imagePreview.classList.add('hidden');
                } else {
                    imagePreview.src = fullUrl;
                    imagePreview.classList.remove('hidden');
                    videoPreview.classList.add('hidden');
                }
                imageUrlInput.value = data.url;
            } else {
                alert('Error al subir el archivo');
                limpiarCampoImagen();
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error en la conexión con el servidor.');
            limpiarCampoImagen();
        } finally {
            submitBtn.disabled = false;
        }
    }

    btnRemoveImage.addEventListener('click', (e) => {
        e.preventDefault();
        if(confirm('¿Deseas quitar la foto/video de este evento?')) {
            limpiarCampoImagen();
        }
    });

    function limpiarCampoImagen() {
        previewContainer.classList.add('hidden');
        imagePreview.src = '#';
        videoPreview.src = '';
        imageUrlInput.value = '';
        fileInput.value = '';
    }
</script>
@endpush
@endsection