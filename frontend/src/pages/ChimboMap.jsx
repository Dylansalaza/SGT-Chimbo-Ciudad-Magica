import React, { useEffect, useRef } from 'react';
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet';
import { useDropzone } from 'react-dropzone';
import { useLocation } from 'react-router-dom';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { useTouristPlaces } from '../hooks/useTouristPlaces';
import {
    MapPinIcon,
    CpuChipIcon,
    CheckCircleIcon,
    XMarkIcon,
    MagnifyingGlassIcon,
    BanknotesIcon,
    TagIcon,
    PhotoIcon,
    NoSymbolIcon,
    BookOpenIcon,
    ClockIcon,
    TruckIcon,
    SpeakerWaveIcon,
    ArrowTopRightOnSquareIcon,
    ArrowRightIcon,
} from '@heroicons/react/24/solid';
import { TargetIcon, StopIcon } from '../components/icons/CustomIcons';

// URL del servidor Backend (Laravel)
const LARAVEL_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://127.0.0.1:3000';

// Extrae y normaliza la URL de la foto de un lugar, sea cual sea el formato
// en que llegue del backend (string simple, array de imágenes, objeto {url}).
// Devuelve null si no hay ninguna imagen válida, para poder mostrar un ícono
// de reemplazo en el popup/modal en vez de un <img> roto.
const resolverImagen = (place) => {
    if (!place) return null;
    const imagenInput = place.imagen_url || place.imagen || place.image || place.imagenes || place.ruta;
    if (!imagenInput) return null;

    let rutaString = '';
    if (Array.isArray(imagenInput) && imagenInput.length > 0) {
        const primeraImagen = imagenInput[0];
        rutaString = typeof primeraImagen === 'object' ? (primeraImagen.url || primeraImagen.path || primeraImagen.ruta || '') : primeraImagen;
    } else if (typeof imagenInput === 'object' && imagenInput !== null) {
        rutaString = imagenInput.url || imagenInput.path || imagenInput.ruta || '';
    } else if (typeof imagenInput === 'string') {
        rutaString = imagenInput;
    }

    if (!rutaString || typeof rutaString !== 'string' || rutaString.includes('[object Object]')) {
        return null;
    }
    // Reapunta cualquier ruta con /storage/ al backend actual (cubre datos con puerto viejo).
    const idxStorage = rutaString.indexOf('/storage/');
    if (idxStorage !== -1) return `${LARAVEL_URL}${rutaString.slice(idxStorage)}`;
    if (rutaString.startsWith('http')) return rutaString;
    const barra = rutaString.startsWith('/') ? '' : '/';
    return `${LARAVEL_URL}${barra}${rutaString}`;
};

// 📍 ICONOS SVG PROFESIONALES DE LOS MARCADORES
// Pin vectorial dibujado inline (sin depender de PNGs externos): escala
// perfecto en cualquier pantalla, con degradado, punto interior blanco y
// sombra elíptica bajo la punta. crearPinSVG(colores) genera el HTML y
// crearIconoPin() lo convierte en un icono Leaflet (divIcon).
const crearPinSVG = (c1, c2, tam = 36) => `
    <svg xmlns="http://www.w3.org/2000/svg" width="${tam}" height="${tam * 1.4}" viewBox="0 0 36 50" style="filter: drop-shadow(0 3px 3px rgba(0,0,0,.35));">
        <defs>
            <linearGradient id="grad-${c1.replace('#','')}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="${c1}"/>
                <stop offset="1" stop-color="${c2}"/>
            </linearGradient>
        </defs>
        <path fill="url(#grad-${c1.replace('#','')})" stroke="white" stroke-width="1.5"
              d="M18 1.5C9.4 1.5 2.5 8.4 2.5 17c0 10.8 13 29 14.6 31.1a1.1 1.1 0 0 0 1.8 0C20.5 46 33.5 27.8 33.5 17 33.5 8.4 26.6 1.5 18 1.5z"/>
        <circle cx="18" cy="16.5" r="6" fill="white"/>
        <circle cx="18" cy="16.5" r="2.8" fill="${c2}"/>
    </svg>`;

const crearIconoPin = (c1, c2, tam = 36) => L.divIcon({
    className: '',                       // sin caja blanca por defecto de Leaflet
    html: crearPinSVG(c1, c2, tam),
    iconSize:    [tam, tam * 1.4],
    iconAnchor:  [tam / 2, tam * 1.4 - 2], // la punta del pin toca la coordenada
    popupAnchor: [0, -(tam * 1.4) + 8],
});

// Azul para los lugares normales; rojo (más grande) para el hallazgo de la IA.
const iconoNormal    = crearIconoPin('#059c45', '#00913f', 34);
const iconoDestacado = crearIconoPin('#f43f5e', '#be123c', 44);

// Límites de navegación del mapa (esquina suroeste y noreste). Se ampliaron
// con un margen extra (~10 km por lado) para que los lugares cercanos al
// borde del cantón se puedan ver y centrar bien.
const LIMITES_CHIMBO = [
    [-1.8800, -79.2600],
    [-1.4800, -78.8300]
];

// 🔊 Voces en español preferidas para la lectura en voz alta, de mejor a peor
// calidad percibida (neuronales/online primero, luego voces femeninas conocidas
// del sistema). Se buscan por coincidencia parcial de nombre.
const VOCES_PREFERIDAS = [
    'Google español',
    'Microsoft Elvira Online (Natural)',
    'Microsoft Dalia Online (Natural)',
    'Microsoft Elvira',
    'Microsoft Dalia',
    'Microsoft Helena',
    'Microsoft Sabina',
    'Paulina',
    'Mónica',
    'Monica',
    'Lucía',
    'Lucia',
];

// Recorre las voces disponibles del navegador y elige la mejor para leer en
// voz alta: primero busca por nombre exacto en VOCES_PREFERIDAS, luego
// cualquier voz neuronal en español, y como último recurso cualquier voz
// en español (prioriza es-ES). Devuelve null si no hay ninguna voz en español.
function elegirMejorVoz(voces) {
    if (!voces || voces.length === 0) return null;
    for (const nombre of VOCES_PREFERIDAS) {
        const v = voces.find(v => v.name.toLowerCase().includes(nombre.toLowerCase()));
        if (v) return v;
    }
    // Cualquier voz en español que suene "online/natural" (motor neuronal)
    const neuronal = voces.find(v => /^es/i.test(v.lang) && /online|natural|neural/i.test(v.name));
    if (neuronal) return neuronal;
    // Cualquier voz en español, priorizando España sobre otras variantes
    const esVoces = voces.filter(v => /^es/i.test(v.lang));
    if (esVoces.length) return esVoces.find(v => /es[-_]es/i.test(v.lang)) || esVoces[0];
    return null;
}

// Componente "invisible" (no renderiza nada) que vive DENTRO de <MapContainer>
// solo para tener acceso a la instancia del mapa de Leaflet (useMap) y así:
//   1) fijar el zoom mínimo y los límites geográficos del cantón Chimbo, y
//   2) animar la cámara (flyTo) cada vez que cambian "center"/"zoom" desde
//      fuera (búsquedas, clic en el menú, resultado de IA, chatbot, etc.)
function MapMover({ center, zoom }) {
    const map = useMap();

    useEffect(() => {
        map.setMinZoom(12);
        map.setMaxBounds(LIMITES_CHIMBO);
        map.options.maxBoundsViscosity = 1.0;

        if (center && !isNaN(center[0]) && !isNaN(center[1])) {
            map.flyTo(center, zoom, { duration: 1.8 });
        }
    }, [center, zoom, map]);

    return null;
}

// ============================================================================
// COMPONENTE PRINCIPAL: ChimboMap (ruta /mapa)
// Mapa interactivo (Leaflet) con: búsqueda por imagen mediante IA (CLIP),
// filtros manuales (texto, categoría, precio, menú de navegación directa),
// integración con el chatbot (resalta lugares/comida sugeridos), ficha de
// detalle con lectura en voz alta (Web Speech API) y botón "cómo llegar".
// Todo el estado de datos/búsqueda vive en el hook useTouristPlaces (state).
// ============================================================================
export default function ChimboMap() {
    const state     = useTouristPlaces();
    const location  = useLocation();
    const [mostrarTodos, setMostrarTodos]     = React.useState(false);
    // Guarda el lugar enviado desde el chatbot para marcarlo con icono rojo
    const [chatbotHighlight, setChatbotHighlight] = React.useState(null);

    // 🔊 Lectura en voz alta (Web Speech API, nativo del navegador, sin costo)
    const [hablando, setHablando] = React.useState(false);
    const vocesRef = React.useRef([]);

    // Las voces del navegador se cargan de forma asíncrona (sobre todo en Chrome),
    // por eso se guardan en cuanto están listas y se refrescan si cambian.
    useEffect(() => {
        if (!('speechSynthesis' in window)) return;
        const cargarVoces = () => { vocesRef.current = window.speechSynthesis.getVoices(); };
        cargarVoces();
        window.speechSynthesis.addEventListener('voiceschanged', cargarVoces);
        return () => window.speechSynthesis.removeEventListener('voiceschanged', cargarVoces);
    }, []);

    // Genera y reproduce un audio (título + descripción) del lugar recibido,
    // usando la mejor voz en español disponible en el navegador. No requiere
    // backend ni API key: todo corre en el propio navegador (SpeechSynthesis).
    const leerLugar = (lugar) => {
        if (!lugar || !('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        const texto = `${lugar.nombre}. ${lugar.descripcion || lugar.description || 'Sin descripción disponible.'}`;
        const utter = new SpeechSynthesisUtterance(texto);
        const voz = elegirMejorVoz(vocesRef.current);
        if (voz) {
            utter.voice = voz;
            utter.lang  = voz.lang;
        } else {
            utter.lang = 'es-ES';
        }
        utter.pitch = 1.05;  // un poco más agudo → suena más cálido, menos robótico
        utter.rate  = 0.98;
        utter.onstart = () => setHablando(true);
        utter.onend   = () => setHablando(false);
        utter.onerror = () => setHablando(false);
        window.speechSynthesis.speak(utter);
    };

    // Corta cualquier audio en reproducción y actualiza el ícono del botón
    const detenerLectura = () => {
        if ('speechSynthesis' in window) window.speechSynthesis.cancel();
        setHablando(false);
    };

    // Cierra la ficha de detalle del lugar, deteniendo primero la narración
    // en curso (para que no siga sonando de fondo con el modal ya cerrado).
    const cerrarModalPlace = () => {
        detenerLectura();
        state.setModalPlace(null);
    };

    // Al salir del mapa (cambio de ruta), corta cualquier audio en curso.
    useEffect(() => {
        return () => { if ('speechSynthesis' in window) window.speechSynthesis.cancel(); };
    }, []);

    // 🔊 Lee automáticamente el título y la descripción al abrir la ficha del lugar.
    useEffect(() => {
        if (state.modalPlace) leerLugar(state.modalPlace);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [state.modalPlace]);

    const { getRootProps, getInputProps, isDragActive } = useDropzone({
        onDrop: state.handleImageDrop,
        accept: { 'image/*': [] },
        multiple: false,
    });

    // 📋 PEGAR IMAGEN (Ctrl+V) — permite pegar una foto copiada desde
    // cualquier lado (otra página web, WhatsApp, capturas, etc.) sin
    // necesidad de guardarla primero como archivo.
    useEffect(() => {
        const onPaste = (e) => {
            const items = e.clipboardData?.items;
            if (!items) return;
            for (const item of items) {
                if (item.type.startsWith('image/')) {
                    const file = item.getAsFile();
                    if (file) {
                        e.preventDefault();
                        state.handleImageDrop([file]);
                    }
                    return;
                }
            }
        };
        window.addEventListener('paste', onPaste);
        return () => window.removeEventListener('paste', onPaste);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [state.handleImageDrop]);


    // 📍 EFECTO CHATBOT — centrar, seleccionar y pintar marcador(es)
    useEffect(() => {
        const nav = location.state;
        if (!nav) return;

        const lugares = state.lugares || [];

        // ── Caso: grupo por categoría (miradores, parques, cascadas…) ───
        // Llega desde el chatbot con nav.categoriaKeys = subcadenas de categoría.
        // Filtra los lugares de esa categoría, los resalta como grupo y centra
        // en el primero (o en el lugar concreto si vino con placeId).
        if (nav.categoriaKeys && Array.isArray(nav.categoriaKeys) && nav.categoriaKeys.length) {
            const keys = nav.categoriaKeys.map(k => String(k).toLowerCase());
            const grupo = lugares.filter(l =>
                l.categoria && keys.some(k => l.categoria.toLowerCase().includes(k))
            );
            if (grupo.length > 0) {
                const primero = nav.placeId
                    ? (grupo.find(g => String(g.id) === String(nav.placeId)) || grupo[0])
                    : grupo[0];
                if (typeof state.setMapCenter === 'function') {
                    state.setMapCenter([parseFloat(primero.lat), parseFloat(primero.lng)]);
                    if (typeof state.setMapZoom === 'function') state.setMapZoom(15);
                }
                // _foodGroup es el nombre interno del "grupo a mostrar/resaltar";
                // sirve para cualquier categoría, no solo comida.
                setChatbotHighlight({ ...primero, _foodGroup: grupo });
            }
            return;
        }

        // ── Caso: "lugares de comer en el mapa" (showFood) ──────────────
        if (nav.showFood) {
            const FOOD_KEYS = ['gastro', 'restaurante', 'comida', 'cafetería', 'cafeteria',
                               'cocina', 'alimenta', 'comer', 'food', 'picante', 'mercado'];
            const foodLugares = lugares.filter(l =>
                l.categoria && FOOD_KEYS.some(k => l.categoria.toLowerCase().includes(k))
            );
            // Mostrar solo los marcadores de comida
            if (foodLugares.length > 0) {
                // Centrar en el primer restaurante
                const f = foodLugares[0];
                if (typeof state.setMapCenter === 'function') {
                    state.setMapCenter([parseFloat(f.lat), parseFloat(f.lng)]);
                    if (typeof state.setMapZoom === 'function') state.setMapZoom(15);
                }
                // Resaltar todos los lugares de comida (usar el primero como highlight)
                setChatbotHighlight({ ...f, _foodGroup: foodLugares });
            }
            // Si viene con un lugar específico además, centra en él
            if (nav.lat && nav.lng) {
                if (typeof state.setMapCenter === 'function') {
                    state.setMapCenter([parseFloat(nav.lat), parseFloat(nav.lng)]);
                    if (typeof state.setMapZoom === 'function') state.setMapZoom(16);
                }
            }
            return;
        }

        // ── Caso: lugar turístico específico ────────────────────────────
        if (!nav.lat || !nav.lng) return;
        const lat = parseFloat(nav.lat);
        const lng = parseFloat(nav.lng);
        if (isNaN(lat) || isNaN(lng)) return;

        if (typeof state.setMapCenter === 'function') {
            state.setMapCenter([lat, lng]);
            if (typeof state.setMapZoom === 'function') state.setMapZoom(17);
        }

        const found = lugares.length > 0
            ? (nav.placeId
                ? lugares.find(l => String(l.id) === String(nav.placeId))
                : lugares.find(l =>
                    Math.abs(parseFloat(l.lat) - lat) < 0.0001 &&
                    Math.abs(parseFloat(l.lng) - lng) < 0.0001
                  ))
            : null;

        if (found) {
            if (typeof state.setSelectedPlace === 'function') state.setSelectedPlace(found);
            setChatbotHighlight(found);
        } else if (nav.placeId || nav.nombre) {
            const synth = { id: nav.placeId, lat, lng, nombre: nav.nombre || 'Destino', categoria: '' };
            setChatbotHighlight(synth);
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [location.state, state.lugares]);

    // Referencias a los marcadores del mapa (por id de lugar) para poder
    // abrir su popup automáticamente cuando la IA identifica el lugar.
    const markerRefs = useRef({});

    // 🚀 EFECTO DETECTOR DE IA: Si llega un resultado de CLIP, redirige la cámara de inmediato
    useEffect(() => {
        if (state.searchResult) {
            const lat = parseFloat(state.searchResult.lat || state.searchResult.latitud);
            const lng = parseFloat(state.searchResult.lng || state.searchResult.longitud);

            if (!isNaN(lat) && !isNaN(lng)) {
                // Forzamos el movimiento del mapa hacia las coordenadas del hallazgo de la IA
                if (typeof state.setMapCenter === 'function') {
                    state.setMapCenter([lat, lng]);
                    if (typeof state.setMapZoom === 'function') state.setMapZoom(16);
                }
                // Activamos la selección inferior automáticamente
                if (typeof state.setSelectedPlace === 'function') {
                    state.setSelectedPlace(state.searchResult);
                }
                // Abrimos de una vez el popup del marcador rojo (con la foto),
                // con una pequeña espera para que el mapa termine de moverse.
                const id = state.searchResult.id;
                setTimeout(() => {
                    markerRefs.current[id]?.openPopup();
                }, 500);
            }
        }
    }, [state.searchResult]);

    if (state.cargando) {
        return (
            <div className="flex flex-col items-center justify-center min-h-screen text-gray-500 dark:text-gray-400 font-medium">
                <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-green-500 mb-4" />
                <p>Cargando mapas y registros del sistema...</p>
            </div>
        );
    }

    // Los marcadores aparecen SOLO cuando el usuario busca algo:
    //   - identificó un lugar por imagen (IA), o
    //   - escribió texto, eligió categoría, rango de precio o navegó a un destino.
    // Por defecto (sin búsqueda) el mapa va limpio, sin punteros.
    const hayBusqueda = !!(
        state.searchResult ||
        (state.searchTerm && state.searchTerm.trim()) ||
        (state.categoriasSeleccionadas && state.categoriasSeleccionadas.length) ||
        state.lugarSeleccionadoMenu ||
        (state.filtroPrecio && state.filtroPrecio !== 'todos')
    );

    const lugaresBase = mostrarTodos
        ? [...(state.lugares || [])]
        : state.searchResult
            ? [state.searchResult]
            : (hayBusqueda ? [...(state.filteredPlaces || [])] : []);

    // chatbotActivo: controla qué lugares MOSTRAR (solo cuando no hay búsqueda activa)
    // El color rojo del marcador se calcula por separado y funciona siempre.
    const chatbotActivo = !!chatbotHighlight && !hayBusqueda && !state.searchResult;
    const foodGroup     = chatbotActivo ? chatbotHighlight?._foodGroup : null;

    const lugaresAMapear = chatbotActivo && !mostrarTodos
        ? (foodGroup
            ? foodGroup                          // solo restaurantes
            : [chatbotHighlight])                // solo el lugar específico
        : lugaresBase;                           // comportamiento normal del mapa

    // Lugares similares (recomendaciones):
    //   1º preferimos los de la MISMA categoría (ej. otros parques).
    //   2º si no hay ninguno, usamos los visualmente parecidos que devuelve el
    //      motor CLIP (state.similares), para que SIEMPRE haya recomendaciones.
    const categoriaResultado = state.searchResult?.categoria;
    const resId = state.searchResult ? parseInt(state.searchResult.id) : null;

    const mismaCategoria = state.searchResult
        ? (state.lugares || []).filter(l =>
            categoriaResultado &&
            l.categoria === categoriaResultado &&
            parseInt(l.id) !== resId)
        : [];

    const parecidosVisuales = state.searchResult
        ? (state.similares || []).filter(s => parseInt(s.id) !== resId)
        : [];

    const similares = mismaCategoria.length > 0 ? mismaCategoria : parecidosVisuales;
    const tipoSimilares = mismaCategoria.length > 0 ? 'categoria' : 'visual';

    return (
        <div className="max-w-7xl mx-auto px-4 py-6 font-sans">
            <div className="text-center mb-6 animate-fade-in-up">
                <span className="inline-block h-1 w-24 rounded-full brand-gradient-animated mb-3" />
                <h1 className="font-serif text-3xl font-bold text-gray-800 dark:text-white">San José de Chimbo</h1>
            </div>

            <div className="flex flex-col lg:flex-row gap-5">

                {/* 🛠️ PANEL DE CONTROLES */}
                <div className="lg:w-64 flex flex-col gap-4 shrink-0 animate-fade-in-up">
                    
                    {/* Buscador por Red Neuronal (IA) */}
                    <div className="bg-gradient-to-b from-green-50 to-gold-50 dark:from-gray-800 dark:to-gray-800 rounded-xl p-4 shadow-sm border border-green-100 dark:border-gray-700">
                        <p className="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3 text-center flex items-center justify-center gap-1.5"><CpuChipIcon className="w-4 h-4" /> Buscar por imagen (IA)</p>

                        <div {...getRootProps()} className={`border-2 border-dashed rounded-lg p-3 text-center cursor-pointer transition-all mb-3 ${isDragActive ? 'border-green-500 bg-green-100 dark:bg-green-900/30' : state.uploadedImage ? 'border-green-400 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-green-400 bg-white dark:bg-[#242424]'}`}>
                            <input {...getInputProps()} />
                            {state.uploadedImage ? (
                                <div className="flex items-center gap-2">
                                    <img src={state.uploadedImage} alt="preview" className="h-12 w-12 object-cover rounded-lg shadow-sm" />
                                    <div className="text-left flex-1">
                                        <p className="text-green-600 dark:text-green-400 text-xs font-bold flex items-center gap-1"><CheckCircleIcon className="w-3.5 h-3.5" /> Imagen Lista</p>
                                    </div>
                                    <button type="button" onClick={(e) => { e.stopPropagation(); state.limpiarBusquedaIA(); }} className="text-gray-400 hover:text-red-500 px-1"><XMarkIcon className="w-4 h-4" /></button>
                                </div>
                            ) : (
                                <p className="text-xs text-gray-500 dark:text-gray-400 py-2">Arrastra, haz clic o pega (Ctrl+V) una foto</p>
                            )}
                        </div>


                        <button 
                            type="button"
                            onClick={state.ejecutarBusquedaIA} 
                            disabled={!state.uploadedImage || state.searching} 
                            className="btn-press w-full py-2 rounded-lg text-sm font-bold bg-gradient-to-r from-green-600 to-green-700 text-white shadow-green-md disabled:opacity-50 disabled:cursor-not-allowed hover:opacity-95"
                        >
                            {state.searching ? 'Analizando Atractivo...' : <span className="flex items-center justify-center gap-1.5"><MagnifyingGlassIcon className="w-4 h-4" /> Identificar Destino</span>}
                        </button>
                        
                        {state.resultadoEsIA && state.searchResult && (
                            <button
                                type="button"
                                onClick={() => { state.limpiarBusquedaIA(); if (state.limpiarFiltros) state.limpiarFiltros(); }}
                                className="w-full mt-2 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-bold hover:bg-red-200 transition-colors flex items-center justify-center gap-1"
                            >
                                <XMarkIcon className="w-3.5 h-3.5" /> Quitar resultado IA
                            </button>
                        )}
                        
                        {state.error && <p className="mt-2 text-xs text-red-600 text-center font-medium">{state.error}</p>}
                    </div>

                    {/* Filtros Geográficos y de Atributos */}
                    <div className="bg-white dark:bg-[#242424] rounded-xl shadow p-4 space-y-4 border border-gray-100 dark:border-gray-700">
                        <div>
                            <label className="flex items-center gap-1 text-xs font-bold text-gray-600 dark:text-gray-300 mb-1 tracking-wider"><MapPinIcon className="w-3.5 h-3.5" /> NAVEGAR A DESTINO</label>
                            <select value={state.lugarSeleccionadoMenu || ""} onChange={(e) => state.seleccionarDesdeMenu(e.target.value)} className="w-full p-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none bg-white dark:bg-[#242424] text-gray-800 dark:text-gray-100">
                                <option value="">— Selecciona —</option>
                                {state.lugares?.map(l => <option key={l.id} value={l.id}>{l.nombre}</option>)}
                            </select>
                        </div>

                        <div>
                            <label className="flex items-center gap-1 text-xs font-bold text-gray-600 dark:text-gray-300 mb-1 tracking-wider"><MagnifyingGlassIcon className="w-3.5 h-3.5" /> TEXTO</label>
                            <input type="text" placeholder="ej. Cascada, Iglesia..." value={state.searchTerm || ""} onChange={(e) => state.setSearchTerm(e.target.value)} className="w-full p-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none bg-gray-50/50 dark:bg-[#242424] text-gray-800 dark:text-gray-100"/>
                        </div>

                        <div>
                            <h3 className="flex items-center gap-1 text-xs font-bold text-gray-600 dark:text-gray-300 mb-2 tracking-wider"><BanknotesIcon className="w-3.5 h-3.5" /> RANGO PRECIO</h3>
                            {['todos', 'gratis', 'economico', 'premium'].map(mode => (
                                <label key={mode} className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 capitalize cursor-pointer mb-1.5 font-medium">
                                    <input type="radio" checked={state.filtroPrecio === mode} onChange={() => state.setFiltroPrecio(mode)} className="accent-green-600" /> {mode}
                                </label>
                            ))}
                        </div>

                        <div className="border-t border-gray-100 dark:border-gray-700 pt-3">
                            <h3 className="flex items-center gap-1 text-xs font-bold text-gray-600 dark:text-gray-300 mb-2 tracking-wider"><TagIcon className="w-3.5 h-3.5" /> CATEGORÍAS</h3>

                            {/* Opción "Todos" */}
                            <label className="flex items-center gap-2 text-xs font-bold text-green-700 dark:text-green-300 cursor-pointer mb-2 bg-green-50 dark:bg-green-900/30 px-2 py-1.5 rounded-lg border border-green-200 dark:border-green-800">
                                <input
                                    type="checkbox"
                                    checked={mostrarTodos}
                                    onChange={() => {
                                        const nuevoEstado = !mostrarTodos;
                                        setMostrarTodos(nuevoEstado);
                                        if (nuevoEstado) state.setCategoriasSeleccionadas([]);
                                    }}
                                    className="accent-green-600 rounded"
                                />
                                Mostrar todos los lugares
                            </label>

                            {!mostrarTodos && (state.categoriasDisponibles?.length ? (
                                state.categoriasDisponibles.map(cat => (
                                    <label key={cat} className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 cursor-pointer mb-1.5 font-medium">
                                        <input type="checkbox" checked={state.categoriasSeleccionadas?.includes(cat)} onChange={() => state.toggleCategoria(cat)} className="accent-green-600 rounded" /> {cat}
                                    </label>
                                ))
                            ) : (
                                <p className="text-xs text-gray-400">Sin categorías disponibles</p>
                            ))}
                        </div>

                        <button type="button" onClick={state.limpiarFiltros} className="w-full py-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-200 rounded-lg text-xs font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Limpiar Filtros</button>
                    </div>
                </div>

                {/* 🗺️ LIENZO DEL MAPA INTERACTIVO */}
                <div className="flex-1 bg-white dark:bg-[#242424] rounded-xl shadow p-2 border border-gray-100 dark:border-gray-700">
                    <div className="h-[560px] w-full rounded-xl overflow-hidden shadow-inner relative z-10">
                        <MapContainer 
                            center={state.mapCenter} 
                            zoom={state.mapZoom} 
                            className="h-full w-full"
                            maxBounds={LIMITES_CHIMBO}      
                            maxBoundsViscosity={1.0}  
                        > 
                            <TileLayer 
                                attribution='© OpenStreetMap'
                                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" 
                            />
                            <MapMover center={state.mapCenter} zoom={state.mapZoom} />
                            
                            {lugaresAMapear.map(place => {
                                // El marcador rojo aplica siempre que haya chatbotHighlight,
                                // independientemente de si el usuario activó "mostrar todos" o buscó algo.
                                const hlId        = chatbotHighlight?.id;
                                const hlFoodGroup = chatbotHighlight?._foodGroup;
                                const esResultado =
                                    (state.searchResult && parseInt(place.id) === parseInt(state.searchResult.id)) ||
                                    (hlId && !hlFoodGroup && String(place.id) === String(hlId)) ||
                                    (hlFoodGroup && hlFoodGroup.some(fp => String(fp.id) === String(place.id)));

                                const latMarker = parseFloat(place.lat || place.latitud);
                                const lngMarker = parseFloat(place.lng || place.longitud);

                                if (isNaN(latMarker) || isNaN(lngMarker)) return null;

                                const fotoPopup = resolverImagen(place);

                                return (
                                    <Marker
                                        key={place.id}
                                        position={[latMarker, lngMarker]}
                                        icon={esResultado ? iconoDestacado : iconoNormal}
                                        ref={(m) => { if (m) markerRefs.current[place.id] = m; }}
                                        eventHandlers={{ click: () => state.setSelectedPlace(place) }}
                                    >
                                        <Popup>
                                            <div className="text-center min-w-[180px] max-w-[200px] font-sans">
                                                {fotoPopup ? (
                                                    <div
                                                        className="w-full h-24 overflow-hidden rounded-lg mb-2 shadow-sm cursor-pointer"
                                                        title="Ver detalles completos"
                                                        onClick={() => state.setModalPlace(place)}
                                                    >
                                                        <img src={fotoPopup} alt={place.nombre} loading="lazy" decoding="async" className="w-full h-full object-cover" />
                                                    </div>
                                                ) : (
                                                    <div
                                                        className="w-full h-16 bg-green-50 rounded-lg mb-2 flex items-center justify-center text-xl text-green-400 cursor-pointer"
                                                        title="Ver detalles completos"
                                                        onClick={() => state.setModalPlace(place)}
                                                    ><PhotoIcon className="w-6 h-6" /></div>
                                                )}
                                                
                                                <strong className="block text-sm text-gray-800 leading-tight mb-0.5">{place.nombre}</strong>
                                                <p className="text-xs text-gray-500 m-0 font-medium">{place.categoria}</p>
                                                
                                                <button type="button" onClick={() => state.setModalPlace(place)} className="mt-2 text-xs text-green-600 font-bold block mx-auto underline border-none bg-transparent cursor-pointer">
                                                    Ver detalles completos
                                                </button>
                                            </div>
                                        </Popup>
                                    </Marker>
                                );
                            })}
                        </MapContainer>
                    </div>

                    {/* ===== CUADRO DE RESULTADO IA (lugar exacto + recomendaciones) DEBAJO DEL MAPA ===== */}
                    {state.searchResult ? (
                        <div className="mt-4 bg-white dark:bg-[#242424] rounded-2xl border border-green-200 dark:border-green-800 shadow-md p-5 animate-fadeIn">
                            <div className="flex gap-4">
                                <div className="w-28 h-28 rounded-xl overflow-hidden shrink-0 bg-gray-100 dark:bg-gray-700">
                                    {resolverImagen(state.searchResult) ? (
                                        <img src={resolverImagen(state.searchResult)} alt={state.searchResult.nombre} className="w-full h-full object-cover" />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700"><PhotoIcon className="w-8 h-8" /></div>
                                    )}
                                </div>
                                <div className="min-w-0">
                                    <p className="flex items-center gap-1 text-[11px] font-black text-green-700 dark:text-green-400 uppercase tracking-widest"><CheckCircleIcon className="w-3.5 h-3.5" /> Lugar identificado</p>
                                    <h3 className="text-xl font-extrabold text-gray-800 dark:text-white leading-tight">{state.searchResult.nombre}</h3>
                                    <div className="flex items-center gap-2 mt-1">
                                        {state.searchResult.categoria && (
                                            <span className="text-[10px] font-bold bg-green-50 dark:bg-green-900/40 text-green-700 dark:text-green-300 px-2 py-0.5 rounded-full">{state.searchResult.categoria}</span>
                                        )}
                                        {state.topScore != null && (
                                            <span className="flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400"><TargetIcon className="w-3.5 h-3.5" /> {Math.round(state.topScore * 100)}%</span>
                                        )}
                                    </div>
                                    <p className="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mt-1.5">{state.searchResult.descripcion || state.searchResult.description || ''}</p>
                                    <div className="flex gap-2 mt-3">
                                        <button onClick={() => state.setModalPlace(state.searchResult)} className="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-bold transition"><BookOpenIcon className="w-3.5 h-3.5" /> Ver detalles</button>
                                        <button onClick={() => state.seleccionarDesdeMenu(state.searchResult.id)} className="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition"><MapPinIcon className="w-3.5 h-3.5" /> Ver en el mapa</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ) : state.error ? (
                        <div className="mt-4 bg-white dark:bg-[#242424] rounded-2xl border border-red-200 dark:border-red-800 shadow-sm p-8 text-center animate-fadeIn">
                            <NoSymbolIcon className="w-10 h-10 mx-auto mb-2 text-gray-300" />
                            <p className="font-extrabold text-gray-700 dark:text-gray-200 text-lg">No hay coincidencias</p>
                            <p className="text-xs text-gray-400 mt-1">La imagen no corresponde a ningún lugar registrado.</p>
                        </div>
                    ) : state.selectedPlace && (
                        <div className="mt-4 bg-white dark:bg-[#242424] rounded-2xl border border-green-200 dark:border-green-800 shadow-md p-5 animate-fadeIn">
                            <div className="flex gap-4">
                                <div className="w-28 h-28 rounded-xl overflow-hidden shrink-0 bg-gray-100 dark:bg-gray-700">
                                    {resolverImagen(state.selectedPlace) ? (
                                        <img src={resolverImagen(state.selectedPlace)} alt={state.selectedPlace.nombre} className="w-full h-full object-cover" />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700"><PhotoIcon className="w-8 h-8" /></div>
                                    )}
                                </div>
                                <div className="min-w-0">
                                    <p className="flex items-center gap-1 text-[11px] font-black text-green-700 dark:text-green-400 uppercase tracking-widest"><CheckCircleIcon className="w-3.5 h-3.5" /> Lugar seleccionado</p>
                                    <h3 className="text-xl font-extrabold text-gray-800 dark:text-white leading-tight">{state.selectedPlace.nombre}</h3>
                                    {state.selectedPlace.categoria && (
                                        <div className="flex items-center gap-2 mt-1">
                                            <span className="text-[10px] font-bold bg-green-50 dark:bg-green-900/40 text-green-700 dark:text-green-300 px-2 py-0.5 rounded-full">{state.selectedPlace.categoria}</span>
                                        </div>
                                    )}
                                    <p className="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mt-1.5">{state.selectedPlace.descripcion || state.selectedPlace.description || ''}</p>
                                    <div className="flex gap-2 mt-3">
                                        <button onClick={() => state.setModalPlace(state.selectedPlace)} className="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-bold transition"><BookOpenIcon className="w-3.5 h-3.5" /> Ver detalles</button>
                                        <button onClick={() => state.seleccionarDesdeMenu(state.selectedPlace.id)} className="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition"><MapPinIcon className="w-3.5 h-3.5" /> Ver en el mapa</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Esta sección se movió ARRIBA (junto al mapa). Se mantiene desactivada
                para no duplicar el cuadro de resultado. */}
            {false && state.searchResult && (
                <div className="mt-10 animate-fadeIn">

                    {/* Lugar exacto identificado */}
                    <div className="rounded-3xl overflow-hidden shadow-xl border border-gray-100 bg-white md:flex">
                        <div className="md:w-2/5 relative h-60 md:h-auto bg-gray-100">
                            {resolverImagen(state.searchResult) ? (
                                <img src={resolverImagen(state.searchResult)} alt={state.searchResult.nombre} className="w-full h-full object-cover" />
                            ) : (
                                <div className="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700"><PhotoIcon className="w-16 h-16" /></div>
                            )}
                            <span className="absolute top-4 left-4 bg-green-600 text-white text-xs font-black px-3 py-1.5 rounded-full shadow-lg flex items-center gap-1">
                                <CheckCircleIcon className="w-3.5 h-3.5" /> Identificado por IA
                            </span>
                            {state.topScore != null && (
                                <span className="absolute bottom-4 left-4 bg-black/60 backdrop-blur-sm text-white text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1">
                                    <TargetIcon className="w-3.5 h-3.5" /> {Math.round(state.topScore * 100)}% de confianza
                                </span>
                            )}
                        </div>
                        <div className="md:w-3/5 p-7 flex flex-col justify-center">
                            {state.searchResult.categoria && (
                                <span className="text-xs font-black uppercase tracking-[0.2em] text-green-600">{state.searchResult.categoria}</span>
                            )}
                            <h2 className="text-3xl font-extrabold text-gray-800 mt-1 leading-tight">{state.searchResult.nombre}</h2>
                            <p className="text-gray-600 mt-3 leading-relaxed line-clamp-3">
                                {state.searchResult.descripcion || state.searchResult.description || 'Lugar turístico de San José de Chimbo.'}
                            </p>
                            <div className="flex flex-wrap gap-3 mt-5">
                                <button onClick={() => state.setModalPlace(state.searchResult)}
                                    className="flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white text-sm font-bold shadow transition">
                                    <BookOpenIcon className="w-4 h-4" /> Ver detalles completos
                                </button>
                                <button onClick={() => state.seleccionarDesdeMenu(state.searchResult.id)}
                                    className="flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-gray-100 text-gray-700 text-sm font-bold hover:bg-gray-200 transition">
                                    <MapPinIcon className="w-4 h-4" /> Ver en el mapa
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Lugares similares */}
                    <div className="mt-8">
                        <div className="flex items-center gap-3 mb-4">
                            <h3 className="text-xl font-extrabold text-gray-800 flex items-center gap-2"><MagnifyingGlassIcon className="w-5 h-5" /> Lugares similares</h3>
                            <span className="text-xs font-bold bg-green-50 text-green-700 px-2.5 py-1 rounded-full">
                                {tipoSimilares === 'categoria' ? state.searchResult.categoria : 'parecidos a tu foto'}
                            </span>
                            <span className="flex-1 border-t border-gray-200" />
                        </div>

                        {similares.length > 0 ? (
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-5">
                                {similares.map(s => (
                                    <div key={s.id} onClick={() => state.seleccionarDesdeMenu(s.id)}
                                        className="group rounded-2xl overflow-hidden shadow-md border border-gray-100 bg-white cursor-pointer hover:-translate-y-1.5 hover:shadow-xl transition-all duration-300">
                                        <div className="h-32 overflow-hidden bg-gray-100">
                                            {resolverImagen(s) ? (
                                                <img src={resolverImagen(s)} alt={s.nombre} loading="lazy" decoding="async" className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700"><PhotoIcon className="w-8 h-8" /></div>
                                            )}
                                        </div>
                                        <div className="p-3.5">
                                            <p className="font-bold text-sm text-gray-800 leading-tight line-clamp-1 group-hover:text-green-600 transition-colors">{s.nombre}</p>
                                            <p className="text-xs text-gray-400 mt-0.5">{s.categoria}</p>
                                            <p className="text-xs text-green-500 font-semibold mt-2 flex items-center gap-1 group-hover:gap-2 transition-all">Ver en el mapa <ArrowRightIcon className="w-3 h-3" /></p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-10 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                                <p className="text-gray-400">No hay otros lugares de la categoría
                                    {categoriaResultado ? ` “${categoriaResultado}”` : ''} por ahora.</p>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* MODAL INFORMATIVO FLOTANTE DEL ATRACTIVO */}
            {state.modalPlace && (
                <div className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-xs" onClick={cerrarModalPlace}>
                    <div className="bg-white dark:bg-[#242424] rounded-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto p-6 relative shadow-2xl border border-gray-100 dark:border-gray-700" onClick={e => e.stopPropagation()}>
                        <button type="button" onClick={cerrarModalPlace} className="absolute top-4 right-4 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-full w-8 h-8 flex items-center justify-center transition-colors"><XMarkIcon className="w-4 h-4" /></button>

                        {(() => {
                            const urlImg = resolverImagen(state.modalPlace);
                            return urlImg ? (
                                <div className="w-full rounded-xl mb-4 shadow-xs bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                                    <img src={urlImg} alt={state.modalPlace.nombre} loading="lazy" decoding="async" className="max-w-full max-h-[60vh] object-contain" />
                                </div>
                            ) : (
                                <div className="w-full h-40 bg-gray-100 dark:bg-gray-700 rounded-xl mb-4 flex items-center justify-center text-gray-400 dark:text-gray-500"><PhotoIcon className="w-10 h-10" /></div>
                            );
                        })()}

                        <div className="flex items-start justify-between gap-3 mb-1">
                            <h2 className="text-2xl font-bold text-gray-800 dark:text-white">{state.modalPlace.nombre}</h2>
                            <button
                                type="button"
                                onClick={() => (hablando ? detenerLectura() : leerLugar(state.modalPlace))}
                                className={`shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold transition-colors ${
                                    hablando
                                        ? 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-500/20 dark:text-red-300'
                                        : 'bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-900/40 dark:text-green-300'
                                }`}
                            >
                                {hablando ? <><StopIcon className="w-3.5 h-3.5" /> Detener</> : <><SpeakerWaveIcon className="w-3.5 h-3.5" /> Escuchar</>}
                            </button>
                        </div>
                        <span className="inline-block bg-green-50 dark:bg-green-900/40 text-green-700 dark:text-green-300 text-xs font-bold px-2.5 py-1 rounded-md mb-4 border border-green-100 dark:border-green-800">{state.modalPlace.categoria}</span>

                        <h3 className="flex items-center gap-1 text-xs font-bold text-gray-400 uppercase tracking-wider mb-1"><BookOpenIcon className="w-3.5 h-3.5" /> Descripción del Atractivo:</h3>
                        <p className="text-gray-600 dark:text-gray-300 text-sm leading-relaxed mb-4">{state.modalPlace.descripcion || state.modalPlace.description || 'Sin descripción detallada registrada.'}</p>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl mb-4 border border-gray-100 dark:border-gray-700 text-gray-600 dark:text-gray-300">
                            <div className="flex items-center gap-1"><MapPinIcon className="w-3.5 h-3.5 shrink-0" /> <strong className="text-gray-800 dark:text-white">Dirección:</strong> {state.modalPlace.direccion || 'Cantón Chimbo'}</div>
                            <div className="flex items-center gap-1"><ClockIcon className="w-3.5 h-3.5 shrink-0" /> <strong className="text-gray-800 dark:text-white">Horarios:</strong> {state.modalPlace.horario || 'Libre acceso'}</div>
                        </div>

                        <div className="flex justify-end gap-2 border-t pt-4 border-gray-100 dark:border-gray-700">
                            <button
                                type="button"
                                onClick={() => {
                                    const lat = state.modalPlace.lat || state.modalPlace.latitud;
                                    const lng = state.modalPlace.lng || state.modalPlace.longitud;
                                    window.open(
                                        `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`,
                                        '_blank'
                                    );
                                }}
                                className="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-lg transition-colors flex items-center gap-1.5"
                            >
                                <TruckIcon className="w-4 h-4" /> Cómo llegar (en carro) <ArrowTopRightOnSquareIcon className="w-3.5 h-3.5" />
                            </button>
                            <button type="button" onClick={cerrarModalPlace} className="px-5 py-2 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700 transition-colors">Cerrar Ficha</button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
