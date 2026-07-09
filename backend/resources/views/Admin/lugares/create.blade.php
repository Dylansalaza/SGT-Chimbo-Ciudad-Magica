@extends('admin.layouts.app')

@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between gap-4 mb-4 flex-wrap">
        <h2 class="text-xl font-bold flex items-center gap-2"><i class="fas fa-location-dot text-slate-400"></i> Agregar Nuevo Lugar Turístico</h2>

        {{-- 📥 Botón para precargar el formulario desde una Ficha MINTUR (.xlsx/.xlsm).
             No crea el lugar automáticamente: solo llena los campos de abajo para
             que el admin los revise y luego presione "Agregar Lugar". --}}
        <label id="importarFichaBtn" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold cursor-pointer hover:bg-indigo-700 transition shadow-sm">
            <i class="fas fa-file-import"></i>
            <span id="importarFichaTexto">Importar</span>
            <input type="file" id="fichaInput" accept=".xlsx,.xlsm,.xls" class="hidden">
        </label>
    </div>
    <p id="importarFichaAyuda" class="text-xs text-gray-400 -mt-2 mb-4">
        Sube la "Ficha de Levantamiento y Jerarquización de Atractivos Turísticos" (formato oficial MINTUR) y se precargarán automáticamente los campos que apliquen.
    </p>

    <form method="POST" action="{{ route('admin.lugares.store') }}" id="lugarForm">
        @csrf

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Nombre del lugar *</label>
                <input type="text" name="nombre" id="nombre" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Categoría *</label>
                <div class="flex gap-2">
                    <select name="categoria" id="categoria" class="w-full p-2 border rounded" required>
                        <option value="">Seleccionar categoría</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->nombre }}">{{ $cat->icono }} {{ $cat->nombre }}</option>
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
                <textarea name="descripcion" id="descripcion" rows="3" class="w-full p-2 border rounded" required></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Dirección</label>
                <input type="text" name="direccion" id="direccion" class="w-full p-2 border rounded">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Teléfono</label>
                <input type="text" name="telefono" id="telefono" class="w-full p-2 border rounded">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Horario</label>
                <input type="text" name="horario" id="horario" class="w-full p-2 border rounded" placeholder="Ej: 8:00 - 18:00">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Precio</label>
                <input type="text" name="precio" id="precio" class="w-full p-2 border rounded" placeholder="Ej: Gratis, $5, $20">
            </div>
            
            <!-- Mapa interactivo -->
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-2 flex items-center gap-1.5"><i class="fas fa-map-location-dot text-slate-400"></i> Haz clic en el mapa para marcar la ubicación</label>
                <div id="map" style="height: 400px; width: 100%; border-radius: 1rem; z-index: 1;"></div>
                <div id="coordenadas" class="mt-2 text-sm text-gray-500"></div>
                <input type="hidden" name="lat" id="lat" required>
                <input type="hidden" name="lng" id="lng" required>
            </div>
            
            <!-- Imagen del lugar -->
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-2 flex items-center gap-1.5"><i class="fas fa-camera text-slate-400"></i> Imagen del lugar (arrastra, haz clic o pega)</label>
                <div id="dropzoneArea" class="border-2 border-dashed border-blue-500 rounded-xl p-8 text-center cursor-pointer bg-gray-50 transition hover:bg-blue-50">
                    <i class="fas fa-cloud-upload-alt text-4xl text-blue-500 mb-2 block"></i>
                    <p class="text-gray-600">Arrastra una imagen aquí o haz clic para seleccionar</p>
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF (máx. 2MB) — también puedes pegar con Ctrl+V</p>
                    <input type="file" id="fileInput" accept="image/*" style="display: none;">
                </div>
                <div id="previewContainer" class="mt-3 hidden">
                    <img id="previewImg" class="w-32 h-32 object-cover rounded shadow">
                    <button type="button" id="removeImageBtn" class="mt-1 text-red-500 text-sm hover:underline">Eliminar imagen</button>
                </div>
                <input type="hidden" name="imagen_url" id="imagenUrl">
            </div>
        </div>
        
        @include('admin.partials.galeria-uploader', [
            'uploadRoute' => 'admin.lugares.upload',
            'field'       => 'galeria',
            'titulo'      => '<i class="fas fa-robot text-slate-400"></i> Fotos de referencia para el reconocimiento por imagen',
            'ayuda'       => 'Sube VARIAS fotos reales del lugar (distintos ángulos). Cuantas más fotos, mejor lo reconoce la IA. Luego reindexa con /refresh.',
        ])

        <label class="mt-4 flex items-center gap-3 p-3 rounded-lg border border-slate-200 bg-slate-50 cursor-pointer w-fit">
            <input type="checkbox" name="destacado" value="1" class="w-5 h-5">
            <span class="text-sm font-semibold text-slate-700 flex items-center gap-1.5"><i class="fas fa-star text-slate-400"></i> Mostrar como “Destacado” en el inicio (Home)</span>
        </label>

        <div class="mt-4">
            <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">Agregar Lugar</button>
            <a href="{{ route('admin.lugares.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 ml-2">Cancelar</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Inicializar el mapa centrado en Chimbo
    const map = L.map('map').setView([-1.6765, -79.0468], 14);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    let marker = null;
    
    // Capturar clic en el mapa
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        // Actualizar campos ocultos
        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;
        
        // Mostrar coordenadas
        document.getElementById('coordenadas').innerHTML = `<i class="fas fa-location-dot"></i> Ubicación seleccionada: Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;
        
        // Agregar o mover marcador
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
        }
        marker.bindPopup(`<strong>Ubicación seleccionada</strong><br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`).openPopup();
    });
    
    // Subida de imagen por arrastre
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
    
    dropzoneArea.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        handleFiles(files);
    });
    
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
            dropzoneArea.style.display = 'none';
        } catch (error) {
            console.error('Error:', error);
            alert('Error al subir la imagen');
        }
        fileInput.value = '';
    }
    
    removeImageBtn.addEventListener('click', () => {
        imagenUrlInput.value = '';
        previewContainer.classList.add('hidden');
        dropzoneArea.style.display = 'block';
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

    // =====================================================================
    // 📥 IMPORTAR FICHA MINTUR (.xlsx/.xlsm)
    // Sube el archivo al endpoint admin.lugares.importarFicha, que devuelve
    // solo los campos necesarios para un "Lugar" (nombre, categoría,
    // descripción, dirección, teléfono, precio, lat/lng y fotos). Con esa
    // respuesta precargamos el formulario ya existente, SIN enviarlo:
    // el admin revisa/ajusta y luego guarda con el botón normal.
    // =====================================================================
    const fichaInput      = document.getElementById('fichaInput');
    const importarFichaBtn = document.getElementById('importarFichaBtn');
    const importarFichaTexto = document.getElementById('importarFichaTexto');

    fichaInput.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const textoOriginal = importarFichaTexto.textContent;
        importarFichaTexto.textContent = 'Leyendo ficha...';
        importarFichaBtn.classList.add('opacity-60', 'pointer-events-none');

        const formData = new FormData();
        formData.append('ficha', file);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        try {
            const response = await fetch('{{ route("admin.lugares.importarFicha") }}', {
                method: 'POST',
                body: formData,
            });
            const data = await response.json();

            if (!response.ok) {
                alert(data.error || 'No se pudo leer la ficha.');
                return;
            }

            precargarFormularioDesdeFicha(data);
        } catch (error) {
            console.error('Error:', error);
            alert('Error al importar la ficha.');
        } finally {
            importarFichaTexto.textContent = textoOriginal;
            importarFichaBtn.classList.remove('opacity-60', 'pointer-events-none');
            fichaInput.value = '';
        }
    });

    // Vuelca los datos devueltos por el backend en los campos del formulario.
    function precargarFormularioDesdeFicha(data) {
        if (data.nombre) document.getElementById('nombre').value = data.nombre;
        if (data.descripcion) document.getElementById('descripcion').value = data.descripcion;
        if (data.direccion) document.getElementById('direccion').value = data.direccion;
        if (data.telefono) document.getElementById('telefono').value = data.telefono;
        if (data.precio) document.getElementById('precio').value = data.precio;
        if (data.horario) document.getElementById('horario').value = data.horario;

        // Categoría: si no existe una opción con ese texto exacto, se agrega
        // una nueva (la categoría es un campo de texto libre en la BD, no
        // requiere existir previamente como registro de PlaceCategory).
        if (data.categoria) {
            const select = document.getElementById('categoria');
            const yaExiste = [...select.options].some(
                (opt) => opt.value.toLowerCase() === data.categoria.toLowerCase()
            );
            if (!yaExiste) {
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = data.categoria;
                nuevaOpcion.textContent = data.categoria;
                select.appendChild(nuevaOpcion);
            }
            select.value = data.categoria;
        }

        // Ubicación: actualiza los campos ocultos, el texto de coordenadas
        // y mueve/crea el marcador en el mapa Leaflet ya existente.
        if (data.lat && data.lng) {
            document.getElementById('lat').value = data.lat;
            document.getElementById('lng').value = data.lng;
            document.getElementById('coordenadas').innerHTML =
                `<i class="fas fa-location-dot"></i> Ubicación (de la ficha): Lat: ${data.lat}, Lng: ${data.lng}`;

            if (marker) {
                marker.setLatLng([data.lat, data.lng]);
            } else {
                marker = L.marker([data.lat, data.lng]).addTo(map);
            }
            marker.bindPopup('<strong>Ubicación importada de la ficha</strong>').openPopup();
            map.setView([data.lat, data.lng], 15);
        }

        // Fotos: la primera se usa como portada (imagen_url); el resto se
        // agrega a la galería de referencia para el reconocimiento por IA.
        if (Array.isArray(data.imagenes) && data.imagenes.length > 0) {
            const [portada, ...resto] = data.imagenes;

            imagenUrlInput.value = portada;
            previewImg.src = portada;
            previewContainer.classList.remove('hidden');
            dropzoneArea.style.display = 'none';

            const previews = document.getElementById('galeriaPreviews');
            resto.forEach((url) => {
                const div = document.createElement('div');
                div.className = 'galeria-item relative w-24 h-24 rounded-lg overflow-hidden border border-gray-200';
                div.innerHTML = `
                    <img src="${url}" class="w-full h-full object-cover">
                    <input type="hidden" name="galeria[]" value="${url}">
                    <button type="button" class="galeria-remove absolute top-1 right-1 bg-rose-500 text-white w-5 h-5 rounded-full text-xs leading-none"><i class="fas fa-xmark"></i></button>`;
                previews.appendChild(div);
            });
        }

        // Aviso informativo (no error) visible mínimo 7 segundos.
        showToast('info', 'Ficha importada. Revisa los campos y ajusta lo que falte antes de guardar.', 7000);
    }
</script>
@endpush
@endsection