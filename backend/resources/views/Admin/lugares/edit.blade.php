@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    {{-- Header de Pantalla Completa (mismo patrón que el resto del panel) --}}
    <div class="sticky top-0 z-30 header-corporate text-white w-full px-10 shadow-lg border-b border-white/5">
        <div class="w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
            <div class="space-y-1">
                <a href="{{ route('admin.lugares.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300 hover:text-white transition mb-1">
                    <i class="fas fa-arrow-left text-[10px]"></i> Volver a Lugares Turísticos
                </a>
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl flex items-center gap-3">
                    <i class="fas fa-pen-to-square text-lg text-slate-300"></i> Editar Lugar Turístico
                </h1>
                <p class="text-sm text-slate-300 font-medium">{{ $lugar->nombre }}</p>
            </div>
        </div>
    </div>

    <div class="p-8 w-full">
        <div class="bg-white rounded-2xl card-premium-shadow max-w-5xl mx-auto">
            <form method="POST" action="{{ route('admin.lugares.update', $lugar->id) }}" id="lugarForm" class="p-8 sm:p-10 space-y-10">
                @csrf
                @method('PUT')

                {{-- Sección: Información general --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-circle-info"></i> Información general
                    </h2>
                    <div class="grid md:grid-cols-2 gap-5">
                        <div>
                            <label for="nombre" class="block text-sm font-bold text-slate-700 mb-1.5">Nombre del lugar *</label>
                            <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $lugar->nombre) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm transition" required>
                        </div>
                        <div>
                            <label for="categoria" class="block text-sm font-bold text-slate-700 mb-1.5">Categoría *</label>
                            <div class="flex gap-2">
                                <select name="categoria" id="categoria" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm transition" required>
                                    <option value="">Seleccionar categoría</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->nombre }}" {{ old('categoria', $lugar->categoria) == $cat->nombre ? 'selected' : '' }}>
                                            {{ $cat->icono }} {{ $cat->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                <a href="{{ route('admin.categorias.index') }}"
                                   title="Gestionar categorías"
                                   class="flex items-center gap-1.5 px-3.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 text-sm font-semibold whitespace-nowrap transition">
                                    <i class="fas fa-tags"></i> Gestionar
                                </a>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label for="descripcion" class="block text-sm font-bold text-slate-700 mb-1.5">Descripción *</label>
                            <textarea name="descripcion" id="descripcion" rows="4" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm leading-relaxed transition" required>{{ old('descripcion', $lugar->descripcion) }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Sección: Datos de contacto --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-address-card"></i> Datos de contacto y visita
                    </h2>
                    <div class="grid md:grid-cols-2 gap-5">
                        <div>
                            <label for="direccion" class="block text-sm font-bold text-slate-700 mb-1.5">Dirección</label>
                            <input type="text" name="direccion" id="direccion" value="{{ old('direccion', $lugar->direccion) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm transition">
                        </div>
                        <div>
                            <label for="telefono" class="block text-sm font-bold text-slate-700 mb-1.5">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" value="{{ old('telefono', $lugar->telefono) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm transition">
                        </div>
                        <div>
                            <label for="horario" class="block text-sm font-bold text-slate-700 mb-1.5">Horario</label>
                            <input type="text" name="horario" id="horario" value="{{ old('horario', $lugar->horario) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm transition" placeholder="Ej: 8:00 - 18:00">
                        </div>
                        <div>
                            <label for="precio" class="block text-sm font-bold text-slate-700 mb-1.5">Precio</label>
                            <input type="text" name="precio" id="precio" value="{{ old('precio', $lugar->precio) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm transition" placeholder="Ej: Gratis, $5, $20">
                        </div>
                    </div>
                </section>

                {{-- Sección: Ubicación --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-map-location-dot"></i> Ubicación geográfica
                    </h2>
                    <p class="text-sm text-slate-500 -mt-2">Haz clic en el mapa para cambiar la posición del marcador.</p>
                    <div id="map" style="height: 400px; width: 100%; border-radius: 1rem; z-index: 1;"></div>
                    <div id="coordenadas" class="text-sm text-slate-500"></div>
                    <input type="hidden" name="lat" id="lat" value="{{ old('lat', $lugar->lat) }}" required>
                    <input type="hidden" name="lng" id="lng" value="{{ old('lng', $lugar->lng) }}" required>
                </section>

                {{-- Sección: Imagen principal --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-camera"></i> Imagen del lugar
                    </h2>

                    <div id="dropzoneArea" class="border-2 border-dashed border-green-400 rounded-2xl p-8 sm:p-10 text-center cursor-pointer bg-slate-50/60 hover:bg-green-50 transition {{ $lugar->imagen_url ? 'hidden' : '' }}">
                        <i class="fas fa-cloud-upload-alt text-4xl text-green-500 mb-3 block"></i>
                        <p class="text-slate-600 text-sm font-medium">Arrastra una nueva imagen aquí o haz clic para seleccionar</p>
                        <p class="text-xs text-slate-400 mt-1">JPG, PNG, GIF (máx. 2MB) — también puedes pegar con Ctrl+V</p>
                        <input type="file" id="fileInput" accept="image/*" style="display: none;">
                    </div>

                    <div id="previewContainer" class="{{ $lugar->imagen_url ? '' : 'hidden' }}">
                        <img id="previewImg" src="{{ $lugar->imagen_url }}" class="w-48 h-32 object-cover rounded-xl shadow-md border border-slate-200">
                        <button type="button" id="removeImageBtn" class="mt-2 text-red-500 text-xs font-bold hover:underline flex items-center gap-1">
                            <i class="fas fa-trash-alt"></i> Eliminar e intercambiar imagen
                        </button>
                    </div>
                    <input type="hidden" name="imagen_url" id="imagenUrl" value="{{ old('imagen_url', $lugar->imagen_url) }}">
                </section>

                {{-- Sección: Galería para reconocimiento por IA --}}
                <section class="space-y-5">
                    @include('admin.partials.galeria-uploader', [
                        'uploadRoute' => 'admin.lugares.upload',
                        'field'       => 'galeria',
                        'existing'    => $lugar->galeria ?? [],
                        'titulo'      => '<i class="fas fa-robot text-slate-400"></i> Fotos de referencia para el reconocimiento por imagen',
                        'ayuda'       => 'Sube VARIAS fotos reales del lugar (distintos ángulos). Cuantas más fotos, mejor lo reconoce la IA. Luego reindexa con /refresh.',
                    ])
                </section>

                {{-- Sección: Visibilidad --}}
                <section>
                    <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer w-fit hover:bg-slate-100 transition">
                        <input type="checkbox" name="destacado" value="1" class="w-5 h-5 accent-orange-500" {{ old('destacado', $lugar->destacado) ? 'checked' : '' }}>
                        <span class="text-sm font-semibold text-slate-700 flex items-center gap-1.5"><i class="fas fa-star text-slate-400"></i> Mostrar como “Destacado” en el inicio (Home)</span>
                    </label>
                </section>

                {{-- Acciones --}}
                <div class="flex items-center gap-3 pt-6 border-t border-slate-100">
                    <button type="submit" class="px-6 py-2.5 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl text-sm transition-all shadow-md inline-flex items-center gap-2">
                        <i class="fas fa-floppy-disk"></i> Guardar Cambios
                    </button>
                    <a href="{{ route('admin.lugares.index') }}" class="px-6 py-2.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold rounded-xl text-sm transition-all">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // 🎛️ INICIALIZAR MAPA CON COORDENADAS EXISTENTES
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    
    // Si existen coordenadas en la DB usa esas, sino usa el centro por defecto de Chimbo
    const initialLat = parseFloat(latInput.value) || -1.6765;
    const initialLng = parseFloat(lngInput.value) || -79.0468;
    
    const map = L.map('map').setView([initialLat, initialLng], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    
    // Renderizar el marcador en su posición guardada de la Base de Datos
    let marker = L.marker([initialLat, initialLng]).addTo(map);
    marker.bindPopup(`<strong>${document.getElementById('nombre').value || 'Lugar'}</strong><br>Lat: ${initialLat}<br>Lng: ${initialLng}`).openPopup();
    document.getElementById('coordenadas').innerHTML = `<i class="fas fa-location-dot"></i> Ubicación seleccionada: Lat: ${initialLat.toFixed(6)}, Lng: ${initialLng.toFixed(6)}`;

    // Escuchar el clic para reubicar el marcador
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        latInput.value = lat;
        lngInput.value = lng;
        
        document.getElementById('coordenadas').innerHTML = `<i class="fas fa-location-dot"></i> Ubicación seleccionada: Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;
        
        marker.setLatLng([lat, lng]);
        marker.bindPopup(`<strong>Nueva ubicación</strong><br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`).openPopup();
    });
    
    // 📸 LÓGICA DE SUBIDA Y CONTROL DE IMÁGENES AJAX
    const dropzoneArea = document.getElementById('dropzoneArea');
    const fileInput = document.getElementById('fileInput');
    const previewContainer = document.getElementById('previewContainer');
    const previewImg = document.getElementById('previewImg');
    const imagenUrlInput = document.getElementById('imagenUrl');
    const removeImageBtn = document.getElementById('removeImageBtn');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzoneArea.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
    });
    
    dropzoneArea.addEventListener('dragenter', () => dropzoneArea.classList.add('bg-green-100'));
    dropzoneArea.addEventListener('dragleave', () => dropzoneArea.classList.remove('bg-green-100'));
    dropzoneArea.addEventListener('drop', () => dropzoneArea.classList.remove('bg-green-100'));
    
    dropzoneArea.addEventListener('drop', (e) => handleFiles(e.dataTransfer.files));
    dropzoneArea.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', (e) => handleFiles(e.target.files));
    
    async function handleFiles(files) {
        if (files.length === 0) return;
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
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        
        try {
            const response = await fetch('{{ route("admin.lugares.upload") }}', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            imagenUrlInput.value = data.url;
            previewImg.src = data.url;
            previewContainer.classList.remove('hidden');
            dropzoneArea.classList.add('hidden');
        } catch (error) {
            console.error('Error:', error);
            alert('Error al subir la imagen');
        }
        fileInput.value = '';
    }
    
    removeImageBtn.addEventListener('click', () => {
        imagenUrlInput.value = '';
        previewContainer.classList.add('hidden');
        dropzoneArea.classList.remove('hidden');
    });

    // =====================================================================
    // 📋 PEGAR IMAGEN DESDE EL PORTAPAPELES
    // Permite copiar una foto en cualquier lado (otra web, WhatsApp, una
    // captura de pantalla, etc.) y pegarla aquí con Ctrl+V o con el botón
    // "Pegar foto copiada". La imagen pegada sigue el mismo flujo que una
    // arrastrada: se sube al servidor con handleFiles().
    // =====================================================================
    // Ctrl+V: si la portada está vacía se usa como portada; si ya hay
    // portada, la imagen pegada se agrega a la galería de referencia.
    document.addEventListener('paste', (e) => {
        const items = e.clipboardData?.items || [];
        for (const item of items) {
            if (item.type.startsWith('image/')) {
                e.preventDefault();
                const file = item.getAsFile();
                if (!file) return;
                if (!imagenUrlInput.value) {
                    handleFiles([file]);
                } else if (window.galeriaSubirArchivo) {
                    window.galeriaSubirArchivo(file);
                }
                return;
            }
        }
    });
</script>
@endpush
@endsection