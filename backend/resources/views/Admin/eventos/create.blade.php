@extends('admin.layouts.app')

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-xl font-bold mb-4 flex items-center gap-2"><i class="fas fa-calendar-days text-slate-400"></i> Crear Nuevo Evento</h2>

    <form method="POST" action="{{ route('admin.eventos.store') }}" id="eventoForm">
        @csrf

        <div class="space-y-4">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Título *</label>
                    <input type="text" name="title" id="title" class="w-full p-2 border rounded" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Categoría</label>
                    <input type="text" name="categoria" list="cats-eventos" class="w-full p-2 border rounded" placeholder="Ej: Cultural, Religioso, Deportivo...">
                    <datalist id="cats-eventos">
                        <option value="Cultural"><option value="Religioso"><option value="Deportivo"><option value="Gastronómico"><option value="Musical"><option value="Feria">
                    </datalist>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Descripción</label>
                    <textarea name="description" id="description" rows="1" class="w-full p-2 border rounded"></textarea>
                </div>
            </div>

            {{-- ⏱️ Bloque Separado de Fechas y Horas --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Fecha Inicio</label>
                    <input type="date" name="starts_date" id="starts_date" class="w-full p-2 border rounded bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Hora Inicio</label>
                    <input type="time" name="starts_time" id="starts_time" class="w-full p-2 border rounded bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Fecha Fin</label>
                    <input type="date" name="ends_date" id="ends_date" class="w-full p-2 border rounded bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Hora Fin</label>
                    <input type="time" name="ends_time" id="ends_time" class="w-full p-2 border rounded bg-white">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 flex items-center gap-1.5"><i class="fas fa-camera text-slate-400"></i> Foto o video de portada del evento (arrastra o haz clic)</label>

                <div id="dropzoneArea" class="border-2 border-dashed border-blue-500 rounded-xl p-8 text-center cursor-pointer bg-gray-50 hover:bg-blue-50 transition">
                    <i class="fas fa-cloud-upload-alt text-4xl text-blue-500 mb-2 block"></i>
                    <p class="text-gray-600">Arrastra una foto o video aquí, o haz clic para seleccionar</p>
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF, WEBP o MP4/MOV/WEBM (máx. 25MB)</p>
                    <input type="file" id="fileInput" accept="image/*,video/*" style="display: none;">
                </div>

                <div id="previewContainer" class="mt-3 hidden">
                    <img id="previewImg" class="w-32 h-32 object-cover rounded shadow hidden">
                    <video id="previewVideo" class="w-48 h-32 object-cover rounded shadow hidden" controls muted></video>
                    <p id="previewUrl" class="text-xs text-blue-600 mt-1"></p>
                    <button type="button" id="removeImageBtn" class="mt-1 text-red-500 text-sm hover:underline">
                        Eliminar
                    </button>
                </div>

                <input type="hidden" name="image_url" id="imageUrl">
            </div>

            @include('admin.partials.galeria-uploader', ['uploadRoute' => 'admin.eventos.upload'])
        </div>

        <div class="mt-6 border-t pt-4">
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 font-semibold text-sm">
                Crear Evento
            </button>
            <a href="{{ route('admin.eventos.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 ml-2 text-sm">
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
    const previewVideo     = document.getElementById('previewVideo');
    const previewUrl       = document.getElementById('previewUrl');
    const imageUrlInput    = document.getElementById('imageUrl');
    const removeImageBtn   = document.getElementById('removeImageBtn');

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
        if (!files.length) return;
        const file = files[0];

        const esImagen = file.type.startsWith('image/');
        const esVideo  = file.type.startsWith('video/');
        if (!esImagen && !esVideo) {
            alert('Solo se permiten fotos, GIFs o videos');
            return;
        }
        if (file.size > 25 * 1024 * 1024) {
            alert('El archivo no puede superar los 25MB');
            return;
        }

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
                imageUrlInput.value = data.url;
                if (data.type === 'video') {
                    previewVideo.src = '{{ url("/") }}' + data.url;
                    previewVideo.classList.remove('hidden');
                    previewImg.classList.add('hidden');
                } else {
                    previewImg.src = '{{ url("/") }}' + data.url;
                    previewImg.classList.remove('hidden');
                    previewVideo.classList.add('hidden');
                }
                previewUrl.textContent = (data.type === 'video' ? 'Video subido: ' : 'Imagen subida: ') + data.url;
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

    removeImageBtn.addEventListener('click', () => {
        imageUrlInput.value  = '';
        previewImg.src       = '';
        previewVideo.src     = '';
        previewImg.classList.add('hidden');
        previewVideo.classList.add('hidden');
        previewContainer.classList.add('hidden');
        dropzoneArea.style.display = 'block';
    });
</script>
@endpush
@endsection