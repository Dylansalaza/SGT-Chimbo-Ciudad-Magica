@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    {{-- Header de Pantalla Completa (mismo patrón que el resto del panel) --}}
    <div class="sticky top-0 z-30 header-corporate text-white w-full px-10 shadow-lg border-b border-white/5">
        <div class="w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
            <div class="space-y-1">
                <a href="{{ route('admin.eventos.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 hover:text-white transition mb-1">
                    <i class="fas fa-arrow-left text-[10px]"></i> Volver a Eventos
                </a>
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl flex items-center gap-3">
                    <i class="fas fa-pen-to-square text-lg text-slate-300"></i> Editar Evento
                </h1>
                <p class="text-sm text-slate-300 font-medium">{{ $evento->title }}</p>
            </div>
        </div>
    </div>

    <div class="p-8 w-full">
        <div class="bg-white rounded-2xl card-premium-shadow max-w-4xl mx-auto">
            <form method="POST" action="{{ route('admin.eventos.update', $evento->id) }}" id="eventoForm" class="p-8 sm:p-10 space-y-10">
                @csrf
                @method('PUT')

                {{-- Sección: Información general --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-circle-info"></i> Información general
                    </h2>
                    <div>
                        <label for="title" class="block text-sm font-bold text-slate-700 mb-1.5">Título del Evento *</label>
                        <input type="text" name="title" id="title" value="{{ old('title', $evento->title) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00913f] focus:ring-2 focus:ring-[#00913f]/20 outline-none text-sm transition" required>
                    </div>
                    <div>
                        <label for="categoria" class="block text-sm font-bold text-slate-700 mb-1.5">Categoría</label>
                        @include('admin.partials.categoria-select', ['categorias' => $categorias, 'current' => old('categoria', $evento->categoria)])
                    </div>
                </section>

                {{-- Sección: Fechas --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-calendar-days"></i> Fechas del evento
                    </h2>
                    {{-- Fecha y hora SEPARADAS (mismo patrón que el formulario de crear).
                         El controlador (update) recombina starts_date+starts_time y
                         ends_date+ends_time en los campos starts_at/ends_at. --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label for="starts_date" class="block text-sm font-bold text-slate-700 mb-1.5">Fecha Inicio</label>
                            <input type="date" name="starts_date" id="starts_date"
                                   value="{{ old('starts_date', $evento->starts_at ? \Carbon\Carbon::parse($evento->starts_at)->format('Y-m-d') : '') }}"
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00913f] focus:ring-2 focus:ring-[#00913f]/20 outline-none text-sm transition">
                        </div>
                        <div>
                            <label for="starts_time" class="block text-sm font-bold text-slate-700 mb-1.5">Hora Inicio</label>
                            <input type="time" name="starts_time" id="starts_time"
                                   value="{{ old('starts_time', $evento->starts_at ? \Carbon\Carbon::parse($evento->starts_at)->format('H:i') : '') }}"
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00913f] focus:ring-2 focus:ring-[#00913f]/20 outline-none text-sm transition">
                        </div>
                        <div>
                            <label for="ends_date" class="block text-sm font-bold text-slate-700 mb-1.5">Fecha Fin</label>
                            <input type="date" name="ends_date" id="ends_date"
                                   value="{{ old('ends_date', $evento->ends_at ? \Carbon\Carbon::parse($evento->ends_at)->format('Y-m-d') : '') }}"
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00913f] focus:ring-2 focus:ring-[#00913f]/20 outline-none text-sm transition">
                        </div>
                        <div>
                            <label for="ends_time" class="block text-sm font-bold text-slate-700 mb-1.5">Hora Fin</label>
                            <input type="time" name="ends_time" id="ends_time"
                                   value="{{ old('ends_time', $evento->ends_at ? \Carbon\Carbon::parse($evento->ends_at)->format('H:i') : '') }}"
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00913f] focus:ring-2 focus:ring-[#00913f]/20 outline-none text-sm transition">
                        </div>
                    </div>
                </section>

                {{-- Sección: Descripción --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-align-left"></i> Descripción
                    </h2>
                    <div>
                        <label for="description" class="block text-sm font-bold text-slate-700 mb-1.5">Detalle del evento</label>
                        <textarea name="description" id="description" rows="5"
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00913f] focus:ring-2 focus:ring-[#00913f]/20 outline-none text-sm leading-relaxed transition">{{ old('description', $evento->description) }}</textarea>
                    </div>
                </section>

                {{-- Sección: Multimedia --}}
                @php
                    $esVideoActual = $evento->image_url && \Illuminate\Support\Str::endsWith(strtolower($evento->image_url), ['.mp4', '.mov', '.webm']);
                @endphp
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-camera"></i> Foto o video de portada
                    </h2>

                    <div id="dropzoneArea" class="border-2 border-dashed border-green-400 rounded-2xl p-8 sm:p-10 text-center cursor-pointer bg-slate-50/60 hover:bg-green-50 transition">
                        <i class="fas fa-cloud-upload-alt text-4xl text-green-500 mb-3 block"></i>
                        <p class="text-slate-600 text-sm font-medium">Arrastra una nueva foto o video aquí, o haz clic para cambiarla</p>
                        <p class="text-xs text-slate-400 mt-1">JPG, PNG, GIF, WEBP o MP4/MOV/WEBM (máx. 25MB)</p>
                        <input type="file" id="fileInput" accept="image/*,video/*" style="display: none;">
                    </div>

                    {{-- Preview e Input Oculto --}}
                    <div id="previewContainer" class="{{ $evento->image_url ? '' : 'hidden' }} max-w-xs relative group border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <img id="imagePreview" src="{{ $evento->image_url && !$esVideoActual ? url($evento->image_url) : '#' }}" class="w-full h-44 object-cover {{ $esVideoActual ? 'hidden' : '' }}">
                        <video id="videoPreview" src="{{ $evento->image_url && $esVideoActual ? url($evento->image_url) : '' }}" class="w-full h-44 object-cover {{ $esVideoActual ? '' : 'hidden' }}" controls muted></video>
                        <button type="button" id="btnRemoveImage" class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-7 h-7 text-xs flex items-center justify-center opacity-90 group-hover:opacity-100 hover:bg-red-600 transition shadow"><i class="fas fa-xmark"></i></button>
                    </div>

                    <input type="hidden" name="image_url" id="image_url" value="{{ old('image_url', $evento->image_url) }}">
                </section>

                {{-- Sección: Galería adicional --}}
                <section class="space-y-5">
                    @include('admin.partials.galeria-uploader', ['uploadRoute' => 'admin.eventos.upload', 'existing' => $evento->images ?? []])
                </section>

                {{-- Acciones --}}
                <div class="flex items-center gap-3 pt-6 border-t border-slate-100">
                    <button type="submit" id="submitBtn" class="px-6 py-2.5 bg-[#00913f] hover:bg-[#059c45] text-white font-bold rounded-xl text-sm transition-all shadow-md inline-flex items-center gap-2">
                        <i class="fas fa-floppy-disk"></i> Guardar Cambios
                    </button>
                    <a href="{{ route('admin.eventos.index') }}" class="px-6 py-2.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold rounded-xl text-sm transition-all">
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
    const videoPreview     = document.getElementById('videoPreview');
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
        confirmarAccion('¿Deseas quitar la foto/video de este evento?', limpiarCampoImagen, {
            titulo: 'Quitar archivo', boton: 'Quitar', icono: 'fa-trash-alt'
        });
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