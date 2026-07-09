@extends('admin.layouts.app')

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-xl font-bold mb-4 flex items-center gap-2"><i class="fas fa-pen-to-square text-slate-400"></i> Editar Lugar Turístico: <span class="text-orange-500">{{ $lugar->nombre }}</span></h2>
    
    <form method="POST" action="{{ route('admin.lugares.update', $lugar->id) }}" id="lugarForm">
        @csrf
        @method('PUT')
        
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Nombre del lugar *</label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $lugar->nombre) }}" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Categoría *</label>
                <div class="flex gap-2">
                    <select name="categoria" id="categoria" class="w-full p-2 border rounded" required>
                        <option value="">Seleccionar categoría</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->nombre }}" {{ old('categoria', $lugar->categoria) == $cat->nombre ? 'selected' : '' }}>
                                {{ $cat->icono }} {{ $cat->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.categorias.index') }}"
                       title="Gestionar categorías"
                       class="flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded border text-sm font-semibold whitespace-nowrap transition">
                        <i class="fas fa-tags"></i> Gestionar
                    </a>
                </div>
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Descripción *</label>
                <textarea name="descripcion" id="descripcion" rows="3" class="w-full p-2 border rounded" required>{{ old('descripcion', $lugar->descripcion) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Dirección</label>
                <input type="text" name="direccion" id="direccion" value="{{ old('direccion', $lugar->direccion) }}" class="w-full p-2 border rounded">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Teléfono</label>
                <input type="text" name="telefono" id="telefono" value="{{ old('telefono', $lugar->telefono) }}" class="w-full p-2 border rounded">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Horario</label>
                <input type="text" name="horario" id="horario" value="{{ old('horario', $lugar->horario) }}" class="w-full p-2 border rounded" placeholder="Ej: 8:00 - 18:00">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Precio</label>
                <input type="text" name="precio" id="precio" value="{{ old('precio', $lugar->precio) }}" class="w-full p-2 border rounded" placeholder="Ej: Gratis, $5, $20">
            </div>
            
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-2 flex items-center gap-1.5"><i class="fas fa-map-location-dot text-slate-400"></i> Ubicación geográfica (Haz clic para cambiar la posición del marcador)</label>
                <div id="map" style="height: 400px; width: 100%; border-radius: 1rem; z-index: 1;"></div>
                <div id="coordenadas" class="mt-2 text-sm text-gray-500"></div>
                <input type="hidden" name="lat" id="lat" value="{{ old('lat', $lugar->lat) }}" required>
                <input type="hidden" name="lng" id="lng" value="{{ old('lng', $lugar->lng) }}" required>
            </div>
            
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-2 flex items-center gap-1.5"><i class="fas fa-camera text-slate-400"></i> Imagen del lugar</label>
                
                <div id="dropzoneArea" class="border-2 border-dashed border-blue-500 rounded-xl p-8 text-center cursor-pointer bg-gray-50 transition hover:bg-blue-50 {{ $lugar->imagen_url ? 'hidden' : '' }}">
                    <i class="fas fa-cloud-upload-alt text-4xl text-blue-500 mb-2 block"></i>
                    <p class="text-gray-600">Arrastra una nueva imagen aquí o haz clic para seleccionar</p>
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF (máx. 2MB) — también puedes pegar con Ctrl+V</p>
                    <input type="file" id="fileInput" accept="image/*" style="display: none;">
                </div>

                <div id="previewContainer" class="mt-3 {{ $lugar->imagen_url ? '' : 'hidden' }}">
                    <img id="previewImg" src="{{ $lugar->imagen_url }}" class="w-48 h-32 object-cover rounded-lg shadow-md border">
                    <button type="button" id="removeImageBtn" class="mt-2 text-red-500 text-xs font-bold hover:underline flex items-center gap-1">
                        <i class="fas fa-trash-alt"></i> Eliminar e intercambiar imagen
                    </button>
                </div>
                <input type="hidden" name="imagen_url" id="imagenUrl" value="{{ old('imagen_url', $lugar->imagen_url) }}">
            </div>
        </div>
        
        @include('admin.partials.galeria-uploader', [
            'uploadRoute' => 'admin.lugares.upload',
            'field'       => 'galeria',
            'existing'    => $lugar->galeria ?? [],
            'titulo'      => '<i class="fas fa-robot text-slate-400"></i> Fotos de referencia para el reconocimiento por imagen',
            'ayuda'       => 'Sube VARIAS fotos reales del lugar (distintos ángulos). Cuantas más fotos, mejor lo reconoce la IA. Luego reindexa con /refresh.',
        ])

        <label class="mt-4 flex items-center gap-3 p-3 rounded-lg border border-slate-200 bg-slate-50 cursor-pointer w-fit">
            <input type="checkbox" name="destacado" value="1" class="w-5 h-5" {{ old('destacado', $lugar->destacado) ? 'checked' : '' }}>
            <span class="text-sm font-semibold text-slate-700 flex items-center gap-1.5"><i class="fas fa-star text-slate-400"></i> Mostrar como “Destacado” en el inicio (Home)</span>
        </label>

        <div class="mt-6 border-t pt-4">
            <button type="submit" class="px-5 py-2.5 bg-orange-500 text-white font-medium rounded-lg hover:bg-orange-600 transition shadow-sm inline-flex items-center gap-2">
                <i class="fas fa-floppy-disk"></i> Guardar Cambios
            </button>
            <a href="{{ route('admin.lugares.index') }}" class="px-5 py-2.5 bg-gray-500 text-white font-medium rounded-lg hover:bg-gray-600 transition ml-2">
                Cancelar
            </a>
        </div>
    </form>
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
    
    dropzoneArea.addEventListener('dragenter', () => dropzoneArea.classList.add('bg-blue-100'));
    dropzoneArea.addEventListener('dragleave', () => dropzoneArea.classList.remove('bg-blue-100'));
    dropzoneArea.addEventListener('drop', () => dropzoneArea.classList.remove('bg-blue-100'));
    
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