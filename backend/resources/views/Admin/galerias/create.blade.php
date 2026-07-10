@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    {{-- Header de Pantalla Completa (mismo patrón que el resto del panel) --}}
    <div class="sticky top-0 z-30 bg-[#00294d] text-white w-full px-10 py-8 shadow-lg border-b border-white/5">
        <div class="w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
            <div class="space-y-1">
                <a href="{{ route('admin.galerias.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 hover:text-white transition mb-1">
                    <i class="fas fa-arrow-left text-[10px]"></i> Volver a Galerías
                </a>
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl flex items-center gap-3">
                    <i class="fas fa-images text-lg text-slate-300"></i> Crear Nueva Galería
                </h1>
                <p class="text-sm text-slate-300 font-medium">Agrupa varias fotos o videos bajo un mismo álbum.</p>
            </div>
        </div>
    </div>

    <div class="p-8 w-full">
        <div class="bg-white rounded-2xl card-premium-shadow max-w-4xl mx-auto">
            <form method="POST" action="{{ route('admin.galerias.store') }}" id="galeriaForm" class="p-8 sm:p-10 space-y-10">
                @csrf

                {{-- Sección: Información general --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-circle-info"></i> Información general
                    </h2>
                    <div>
                        <label for="title" class="block text-sm font-bold text-slate-700 mb-1.5">Título *</label>
                        <input type="text" name="title" id="title" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 outline-none text-sm transition" required>
                    </div>
                </section>

                {{-- Sección: Imágenes --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-camera"></i> Imágenes de la galería
                    </h2>
                    <p class="text-sm text-slate-500 -mt-2">Puedes subir varias a la vez.</p>

                    <div id="dropzoneArea" class="border-2 border-dashed border-purple-400 rounded-2xl p-8 sm:p-10 text-center cursor-pointer bg-slate-50/60 hover:bg-purple-50 transition">
                        <i class="fas fa-cloud-upload-alt text-4xl text-purple-500 mb-3 block"></i>
                        <p class="text-slate-600 text-sm font-medium">Arrastra imágenes aquí o haz clic para seleccionar</p>
                        <p class="text-xs text-slate-400 mt-1">JPG, PNG, GIF (máx. 2MB por imagen)</p>
                        <input type="file" id="fileInput" accept="image/*" multiple style="display: none;">
                    </div>

                    {{-- Preview de imágenes subidas --}}
                    <div id="previewGrid" class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>

                    {{-- Aquí se añaden los inputs hidden dinámicamente --}}
                    <div id="imageInputs"></div>

                    <p class="text-xs text-slate-400">
                        Imágenes agregadas: <span id="imageCount" class="font-bold text-slate-600">0</span>
                    </p>
                </section>

                {{-- Acciones --}}
                <div class="flex items-center gap-3 pt-6 border-t border-slate-100">
                    <button type="submit" id="submitBtn" class="px-6 py-2.5 bg-purple-500 hover:bg-purple-600 disabled:opacity-50 text-white font-bold rounded-xl text-sm transition-all shadow-md inline-flex items-center gap-2">
                        <i class="fas fa-plus"></i> Crear Galería
                    </button>
                    <a href="{{ route('admin.galerias.index') }}" class="px-6 py-2.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold rounded-xl text-sm transition-all">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
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