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
                    <i class="fas fa-pen-to-square text-lg text-slate-300"></i> Editar Galería
                </h1>
                <p class="text-sm text-slate-300 font-medium">{{ $galeria->title }}</p>
            </div>
        </div>
    </div>

    <div class="p-8 w-full">
        <div class="bg-white rounded-2xl card-premium-shadow max-w-4xl mx-auto">
            <form method="POST" action="{{ route('admin.galerias.update', $galeria->id) }}" id="galeriaForm" class="p-8 sm:p-10 space-y-10">
                @csrf
                @method('PUT')

                {{-- Sección: Información general --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-circle-info"></i> Información general
                    </h2>
                    <div>
                        <label for="title" class="block text-sm font-bold text-slate-700 mb-1.5">Título de la Galería *</label>
                        <input type="text" name="title" id="title" value="{{ old('title', $galeria->title) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 outline-none text-sm transition" required>
                    </div>
                </section>

                {{-- Sección: Imágenes --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-camera"></i> Imágenes de la galería
                    </h2>
                    <p class="text-sm text-slate-500 -mt-2">La primera imagen tendrá la marca <i class="fas fa-star text-purple-400"></i> de Portada.</p>

                    <div id="dropzoneArea" class="border-2 border-dashed border-purple-400 rounded-2xl p-8 sm:p-10 text-center cursor-pointer bg-slate-50/60 hover:bg-purple-50 transition">
                        <i class="fas fa-cloud-upload-alt text-4xl text-purple-500 mb-3 block"></i>
                        <p class="text-slate-600 text-sm font-medium">Arrastra más imágenes aquí o haz clic para seleccionar</p>
                        <p class="text-xs text-slate-400 mt-1">JPG, PNG, GIF (máx. 2MB por imagen)</p>
                        <input type="file" id="fileInput" accept="image/*" multiple style="display: none;">
                    </div>

                    {{-- Grid de Previsualización Dinámica --}}
                    <div id="previewGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4"></div>

                    {{-- Contenedor oculto de inputs serializados --}}
                    <div id="imageInputs"></div>

                    <p class="text-xs text-slate-400">
                        Total imágenes: <span id="imageCount" class="font-bold text-slate-600">0</span>
                    </p>
                </section>

                {{-- Acciones --}}
                <div class="flex items-center gap-3 pt-6 border-t border-slate-100">
                    <button type="submit" id="submitBtn" class="px-6 py-2.5 bg-purple-500 hover:bg-purple-600 text-white font-bold rounded-xl text-sm transition-all shadow-md inline-flex items-center gap-2">
                        <i class="fas fa-floppy-disk"></i> Guardar Cambios
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
    // Cargar imágenes existentes desde la base de datos
    let listaImagenes = @json($galeria->images ?? []);

    // Inicializar la vista cargando las fotos actuales
    document.addEventListener("DOMContentLoaded", () => {
        listaImagenes.forEach(url => {
            const idx = totalImagenes++;
            renderizarCard(idx, url);
        });
        reconstruirInputsYContador();
    });

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
        const idx = totalImagenes++;
        const placeholder = document.createElement('div');
        placeholder.id = `card-${idx}`;
        placeholder.className = 'relative w-full h-32 bg-gray-200 rounded-xl flex items-center justify-center border shadow-sm';
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
                listaImagenes.push(data.url);
                actualizarCard(idx, data.url);
                reconstruirInputsYContador();
            } else {
                placeholder.remove();
                totalImagenes--;
                alert('Error al subir: ' + file.name);
            }
        } catch (error) {
            placeholder.remove();
            totalImagenes--;
            console.error('Error:', error);
            alert('Error al conectar con el servidor.');
        }
    }

    function renderizarCard(idx, url) {
        const card = document.createElement('div');
        card.id = `card-${idx}`;
        previewGrid.appendChild(card);
        actualizarCard(idx, url);
    }

    function actualizarCard(idx, url) {
        const card = document.getElementById(`card-${idx}`);
        const fullUrl = url.startsWith('http') ? url : '{{ url("/") }}' + url;
        const esPortada = listaImagenes[0] === url;

        card.className = `relative group border-2 rounded-xl overflow-hidden bg-white shadow-sm transition transform hover:-translate-y-1 ${esPortada ? 'border-purple-500 ring-2 ring-purple-200' : 'border-gray-200'}`;
        
        card.innerHTML = `
            <img src="${fullUrl}" class="w-full h-24 object-cover">
            <div class="p-1 bg-gray-50 flex justify-between items-center gap-1">
                <button type="button" onclick="marcarComoPortada('${url}')" class="text-[10px] font-bold px-1.5 py-0.5 rounded flex items-center gap-0.5 transition ${esPortada ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-purple-100'}">
                    ${esPortada ? '<i class="fas fa-star"></i> Portada' : 'Definir Portada'}
                </button>
                <button type="button" onclick="eliminarFoto(${idx}, '${url}')" class="text-gray-400 hover:text-red-500 p-0.5 text-xs transition" title="Eliminar foto">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
    }

    // Mueve la imagen seleccionada al índice 0 del array y refresca el orden
    function marcarComoPortada(urlSeleccionada) {
        listaImagenes = listaImagenes.filter(url => url !== urlSeleccionada);
        listaImagenes.unshift(urlSeleccionada); // Insertar al inicio

        // Refrescar el diseño visual en la pantalla
        previewGrid.innerHTML = "";
        totalImagenes = 0;
        listaImagenes.forEach(url => {
            renderizarCard(totalImagenes++, url);
        });

        reconstruirInputsYContador();
    }

    function eliminarFoto(idx, url) {
        document.getElementById(`card-${idx}`).remove();
        listaImagenes = listaImagenes.filter(item => item !== url);
        reconstruirInputsYContador();
    }

    function reconstruirInputsYContador() {
        imageInputs.innerHTML = "";
        listaImagenes.forEach(url => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'images[]';
            input.value = url;
            imageInputs.appendChild(input);
        });
        imageCount.textContent = listaImagenes.length;
    }
</script>
@endpush
@endsection