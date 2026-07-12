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
                    <i class="fas fa-location-dot text-lg text-slate-300"></i> Agregar Nuevo Lugar Turístico
                </h1>
                <p class="text-sm text-slate-300 font-medium">Completa los datos o impórtalos desde una Ficha MINTUR.</p>
            </div>

            {{-- 📥 Botón para precargar el formulario desde una Ficha MINTUR (.xlsx/.xlsm).
                 No crea el lugar automáticamente: solo llena los campos de abajo para
                 que el admin los revise y luego presione "Agregar Lugar". --}}
            <label id="importarFichaBtn" class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-xs font-black uppercase tracking-wider cursor-pointer transition shadow-md self-start sm:self-center">
                <i class="fas fa-file-import"></i>
                <span id="importarFichaTexto">Importar Ficha</span>
                <input type="file" id="fichaInput" accept=".xlsx,.xlsm,.xls" class="hidden">
            </label>
        </div>
        <p id="importarFichaAyuda" class="text-xs text-slate-300 mt-3 max-w-2xl">
            Sube la "Ficha de Levantamiento y Jerarquización de Atractivos Turísticos" (formato oficial MINTUR) y se precargarán automáticamente los campos que apliquen.
        </p>
    </div>

    <div class="p-8 w-full">
        <div class="bg-white rounded-2xl card-premium-shadow max-w-5xl mx-auto">
            <form method="POST" action="{{ route('admin.lugares.store') }}" id="lugarForm" class="p-8 sm:p-10 space-y-10">
                @csrf

                {{-- Sección: Información general --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-circle-info"></i> Información general
                    </h2>
                    <div class="grid md:grid-cols-2 gap-5">
                        <div>
                            <label for="nombre" class="block text-sm font-bold text-slate-700 mb-1.5">Nombre del lugar *</label>
                            <input type="text" name="nombre" id="nombre" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm transition" required>
                        </div>
                        <div>
                            <label for="categoria" class="block text-sm font-bold text-slate-700 mb-1.5">Categoría *</label>
                            <div class="flex gap-2">
                                <select name="categoria" id="categoria" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm transition" required>
                                    <option value="">Seleccionar categoría</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->nombre }}">{{ $cat->icono }} {{ $cat->nombre }}</option>
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
                            <textarea name="descripcion" id="descripcion" rows="4" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm leading-relaxed transition" required></textarea>
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
                            <input type="text" name="direccion" id="direccion" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm transition">
                        </div>
                        <div>
                            <label for="telefono" class="block text-sm font-bold text-slate-700 mb-1.5">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm transition">
                        </div>
                        <div>
                            <label for="horario" class="block text-sm font-bold text-slate-700 mb-1.5">Horario</label>
                            <input type="text" name="horario" id="horario" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm transition" placeholder="Ej: 8:00 - 18:00">
                        </div>
                        <div>
                            <label for="precio" class="block text-sm font-bold text-slate-700 mb-1.5">Precio</label>
                            <input type="text" name="precio" id="precio" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 outline-none text-sm transition" placeholder="Ej: Gratis, $5, $20">
                        </div>
                    </div>
                </section>

                {{-- Sección: Ubicación --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-map-location-dot"></i> Ubicación geográfica
                    </h2>
                    <p class="text-sm text-slate-500 -mt-2">Haz clic en el mapa para marcar la ubicación.</p>
                    <div id="map" style="height: 400px; width: 100%; border-radius: 1rem; z-index: 1;"></div>
                    <div id="coordenadas" class="text-sm text-slate-500"></div>
                    <input type="hidden" name="lat" id="lat" required>
                    <input type="hidden" name="lng" id="lng" required>
                </section>

                {{-- Sección: Imagen principal --}}
                <section class="space-y-5">
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2 pb-3 border-b border-slate-100">
                        <i class="fas fa-camera"></i> Imagen del lugar
                    </h2>
                    <div id="dropzoneArea" class="border-2 border-dashed border-green-400 rounded-2xl p-8 sm:p-10 text-center cursor-pointer bg-slate-50/60 hover:bg-green-50 transition">
                        <i class="fas fa-cloud-upload-alt text-4xl text-green-500 mb-3 block"></i>
                        <p class="text-slate-600 text-sm font-medium">Arrastra una imagen aquí o haz clic para seleccionar</p>
                        <p class="text-xs text-slate-400 mt-1">JPG, PNG, GIF (máx. 2MB) — también puedes pegar con Ctrl+V</p>
                        <input type="file" id="fileInput" accept="image/*" style="display: none;">
                    </div>
                    <div id="previewContainer" class="hidden">
                        <img id="previewImg" class="w-32 h-32 object-cover rounded-xl shadow border border-slate-200">
                        <button type="button" id="removeImageBtn" class="mt-2 text-red-500 text-xs font-bold hover:underline flex items-center gap-1">
                            <i class="fas fa-trash-alt"></i> Eliminar imagen
                        </button>
                    </div>
                    <input type="hidden" name="imagen_url" id="imagenUrl">
                </section>

                {{-- Sección: Galería para reconocimiento por IA --}}
                <section class="space-y-5">
                    @include('admin.partials.galeria-uploader', [
                        'uploadRoute' => 'admin.lugares.upload',
                        'field'       => 'galeria',
                        'titulo'      => '<i class="fas fa-robot text-slate-400"></i> Fotos de referencia para el reconocimiento por imagen',
                        'ayuda'       => 'Sube VARIAS fotos reales del lugar (distintos ángulos). Cuantas más fotos, mejor lo reconoce la IA. Luego reindexa con /refresh.',
                    ])
                </section>

                {{-- Sección: Visibilidad --}}
                <section>
                    <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer w-fit hover:bg-slate-100 transition">
                        <input type="checkbox" name="destacado" value="1" class="w-5 h-5 accent-orange-500">
                        <span class="text-sm font-semibold text-slate-700 flex items-center gap-1.5"><i class="fas fa-star text-slate-400"></i> Mostrar como “Destacado” en el inicio (Home)</span>
                    </label>
                </section>

                {{-- Acciones --}}
                <div class="flex items-center gap-3 pt-6 border-t border-slate-100">
                    <button type="submit" class="px-6 py-2.5 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl text-sm transition-all shadow-md inline-flex items-center gap-2">
                        <i class="fas fa-plus"></i> Agregar Lugar
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
    
    dropzoneArea.addEventListener('dragenter', () => dropzoneArea.classList.add('bg-green-100'));
    dropzoneArea.addEventListener('dragleave', () => dropzoneArea.classList.remove('bg-green-100'));
    dropzoneArea.addEventListener('drop', () => dropzoneArea.classList.remove('bg-green-100'));
    
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
                    <button type="button" class="galeria-remove absolute top-1 right-1 bg-red-500 text-white w-5 h-5 rounded-full text-xs leading-none"><i class="fas fa-xmark"></i></button>`;
                previews.appendChild(div);
            });
        }

        // Aviso informativo (no error) visible 7 segundos.
        showAlerta('info', 'Ficha importada. Revisa los campos y ajusta lo que falte antes de guardar.', 7000);
    }
</script>
@endpush
@endsection