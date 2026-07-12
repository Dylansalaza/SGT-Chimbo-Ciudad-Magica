import { useState, useEffect, useMemo, useRef } from 'react';
import axios from 'axios';

const API_BASE        = import.meta.env.VITE_API_URL || 'http://127.0.0.1:3000/api';
const POLL_INTERVAL   = 1500;
const MIN_SCORE_LABEL = 0.25;   // por debajo de esto mostramos "baja confianza"

const api = axios.create({ baseURL: API_BASE });

// ── Clasificación del precio (texto libre → categoría de filtro) ──
// En el admin el precio se escribe a mano ("Gratis", "$5", "$20", "Variable"…),
// así que no es un número. Esta función lo interpreta y lo mapea a una de las
// categorías del filtro del mapa. Los precios "Variable"/desconocidos solo
// aparecen bajo "todos".
function categoriaPrecio(raw) {
    const s = String(raw ?? '').trim().toLowerCase();
    if (s === '' || s === 'gratis' || s === 'free' || s === 'libre') return 'gratis';
    // Extrae el primer número, ignorando símbolos ("$5" → 5, "$ 20,00" → 20).
    const num = parseFloat(s.replace(',', '.').replace(/[^0-9.]/g, ''));
    if (Number.isNaN(num)) return 'desconocido';   // p. ej. "Variable"
    if (num === 0)   return 'gratis';
    if (num <= 10)   return 'economico';
    return 'premium';
}

// ============================================================================
// HOOK: useTouristPlaces
// Centraliza TODO el estado y la lógica de datos del mapa (ChimboMap.jsx):
// carga de lugares, filtros manuales (texto/categoría/precio/menú) y el flujo
// completo de búsqueda por imagen con IA (subir foto → crear ticket → sondear
// /image-search/status hasta que el worker de Python + CLIP termine). Así el
// componente de mapa solo se encarga de la parte visual.
// ============================================================================
export function useTouristPlaces() {

    const [lugares,                 setLugares]                 = useState([]);
    const [cargando,                setCargando]                = useState(true);
    const [selectedPlace,           setSelectedPlace]           = useState(null);
    const [modalPlace,              setModalPlace]              = useState(null);

    const [mapCenter,   setMapCenter]   = useState([-1.6825, -79.0435]);
    const [mapZoom,     setMapZoom]     = useState(14);

    const [searchTerm,              setSearchTerm]              = useState('');
    const [filtroPrecio,            setFiltroPrecio]            = useState('todos');
    const [categoriasSeleccionadas, setCategoriasSeleccionadas] = useState([]);
    const [lugarSeleccionadoMenu,   setLugarSeleccionadoMenu]   = useState('');

    // IA
    const [uploadedImage, setUploadedImage] = useState(null);
    const [rawFile,       setRawFile]       = useState(null);
    const [searching,     setSearching]     = useState(false);
    const [searchResult,  setSearchResult]  = useState(null);   // mejor match
    const [similares,     setSimilares]     = useState([]);     // lugares parecidos
    const [topScore,      setTopScore]      = useState(null);   // confianza 0-1
    const [iaError,       setIaError]       = useState(null);
    // Distingue un resultado obtenido por la IA (foto) de uno elegido a mano
    // (menú desplegable, texto, categoría), ya que ambos comparten "searchResult".
    const [resultadoEsIA, setResultadoEsIA] = useState(false);

    const pollingRef = useRef(null);

    // ── Carga inicial ──────────────────────────────────────────
    // Trae el catálogo completo de lugares turísticos (públicos/activos) una
    // sola vez al montar el mapa.
    useEffect(() => {
        const cargar = async () => {
            setCargando(true);
            try {
                const { data } = await api.get('/tourist-places');
                setLugares(Array.isArray(data) ? data : []);
            } catch (err) {
                console.error('Error cargando destinos:', err);
            } finally {
                setCargando(false);
            }
        };
        cargar();
        return () => clearInterval(pollingRef.current);
    }, []);

    // ── Categorías únicas ──────────────────────────────────────
    // Lista de categorías presentes en los lugares, para pintar los checkboxes
    // de filtro sin repetir valores.
    const categoriasDisponibles = useMemo(() => {
        const cats = lugares.map(l => l.categoria).filter(Boolean);
        return [...new Set(cats)];
    }, [lugares]);

    // ── Filtrado combinado ─────────────────────────────────────
    // Aplica los 3 filtros manuales (texto, categorías marcadas, rango de
    // precio) sobre el catálogo completo. Es independiente del resultado de
    // la búsqueda por IA (searchResult).
    const filteredPlaces = useMemo(() => {
        // Normaliza a minúsculas y sin tildes, para que "restaurante" o
        // "gastronomia" encuentren "Restaurante"/"Gastronómica" aunque el
        // usuario escriba sin acentos.
        const norm = (s) => (s || '').normalize('NFD').replace(/\p{Mn}/gu, '').toLowerCase();
        return lugares.filter(place => {
            // La búsqueda por texto mira nombre + descripción + CATEGORÍA, así
            // escribir "restaurante" encuentra los lugares de esa categoría aunque
            // la palabra no aparezca en su nombre (ej. "Patio de comida").
            const texto = norm(`${place.nombre} ${place.descripcion || ''} ${place.categoria || ''}`);
            const matchText  = texto.includes(norm(searchTerm));
            const matchCat   = categoriasSeleccionadas.length === 0 || categoriasSeleccionadas.includes(place.categoria);
            const matchPrecio =
                filtroPrecio === 'todos'
                    ? true
                    : categoriaPrecio(place.precio ?? place.costo) === filtroPrecio;
            return matchText && matchCat && matchPrecio;
        });
    }, [lugares, searchTerm, categoriasSeleccionadas, filtroPrecio]);

    // ── Navegación por menú ────────────────────────────────────
    // Selección manual de un lugar desde el <select> "Navegar a destino":
    // centra el mapa en él y lo marca como resultado (NO viene de la IA, por
    // eso resultadoEsIA se pone en false, para no mostrar el botón "Quitar
    // resultado IA" cuando en realidad fue una selección manual).
    const seleccionarDesdeMenu = (id) => {
        setLugarSeleccionadoMenu(id);
        setResultadoEsIA(false);
        if (!id) { setSearchResult(null); return; }
        const lugar = lugares.find(l => parseInt(l.id) === parseInt(id));
        if (!lugar) return;
        setMapCenter([parseFloat(lugar.lat || lugar.latitud), parseFloat(lugar.lng || lugar.longitud)]);
        setMapZoom(17);
        setSelectedPlace(lugar);
        setSearchResult(lugar);
    };

    // Búsqueda por texto. Al escribir se descarta cualquier resultado previo de
    // la búsqueda por imagen (IA): sin esto, un resultado de IA activo tenía
    // prioridad sobre los filtros manuales en el mapa y la búsqueda por texto
    // "no respondía" hasta limpiar filtros.
    const buscarPorTexto = (valor) => {
        setSearchTerm(valor);
        if (valor && valor.trim()) {
            setSearchResult(null);
            setResultadoEsIA(false);
        }
    };

    // Marca/desmarca una categoría en el filtro (checkbox múltiple). También
    // descarta el resultado de IA por el mismo motivo que buscarPorTexto.
    const toggleCategoria = (cat) => {
        setSearchResult(null);
        setResultadoEsIA(false);
        setCategoriasSeleccionadas(prev =>
            prev.includes(cat) ? prev.filter(c => c !== cat) : [...prev, cat]
        );
    };

    // Restablece todos los filtros y resultados a su estado inicial
    // (vuelve el mapa a la vista general de San José de Chimbo)
    const limpiarFiltros = () => {
        setSearchTerm('');
        setFiltroPrecio('todos');
        setCategoriasSeleccionadas([]);
        setLugarSeleccionadoMenu('');
        setSearchResult(null);
        setSimilares([]);
        setTopScore(null);
        setSelectedPlace(null);
        setResultadoEsIA(false);
        setMapCenter([-1.6825, -79.0435]);
        setMapZoom(14);
    };

    // ── Drag & drop ────────────────────────────────────────────
    // Guarda la imagen soltada/seleccionada y genera una vista previa local
    // (URL.createObjectURL) sin subirla todavía al servidor.
    const handleImageDrop = (acceptedFiles) => {
        const file = acceptedFiles[0];
        if (!file) return;
        if (uploadedImage) URL.revokeObjectURL(uploadedImage);
        setIaError(null);
        setRawFile(file);
        setUploadedImage(URL.createObjectURL(file));
        setSearchResult(null);
        setSimilares([]);
        setTopScore(null);
    };

    // ── Polling ────────────────────────────────────────────────
    // Consulta cada POLL_INTERVAL ms el estado del ticket de búsqueda por
    // imagen (creado por el backend, procesado por worker.py + clip_service.py
    // en segundo plano) hasta que quede 'completed' o 'failed'. Este patrón de
    // sondeo evita mantener una petición HTTP abierta mientras la IA procesa.
    const iniciarPolling = (ticketId) => {
        clearInterval(pollingRef.current);
        pollingRef.current = setInterval(async () => {
            try {
                const { data } = await api.get(`/image-search/status/${ticketId}`);

                if (data.status === 'completed') {
                    clearInterval(pollingRef.current);

                    if (data.result) {
                        const lugar = data.result;
                        const lat   = parseFloat(lugar.latitud || lugar.lat);
                        const lng   = parseFloat(lugar.longitud || lugar.lng);

                        setResultadoEsIA(true);
                        setSearchResult(lugar);
                        setSelectedPlace(lugar);
                        setSimilares(data.similares || []);
                        setTopScore(data.score ?? null);
                        setMapCenter([lat, lng]);
                        setMapZoom(16);
                    } else {
                        // Sin coincidencia confiable (por debajo del umbral de similitud)
                        setIaError('No hay coincidencias');
                        setTopScore(null);
                    }
                    setSearching(false);
                } else if (data.status === 'failed') {
                    clearInterval(pollingRef.current);
                    setIaError(data.error || 'El análisis falló.');
                    setSearching(false);
                }
            } catch (err) {
                clearInterval(pollingRef.current);
                setIaError('Error de red al consultar el estado.');
                setSearching(false);
            }
        }, POLL_INTERVAL);
    };

    // ── Ejecutar búsqueda IA ───────────────────────────────────
    // Sube la imagen al backend (crea el ticket de búsqueda) y arranca el
    // polling para esperar el resultado del motor CLIP.
    const ejecutarBusquedaIA = async () => {
        if (!rawFile) { setIaError('Selecciona una imagen primero.'); return; }
        setSearching(true);
        setIaError(null);
        setSearchResult(null);
        setSimilares([]);
        setTopScore(null);
        setResultadoEsIA(false);

        try {
            const form = new FormData();
            form.append('image', rawFile);
            const { data } = await api.post('/image-search', form, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            if (!data.search_id) throw new Error('El servidor no devolvió un ticket.');
            iniciarPolling(data.search_id);
        } catch (err) {
            setIaError(err.response?.data?.error || 'No se pudo conectar con el servidor.');
            setSearching(false);
        }
    };

    // ── Limpiar IA ─────────────────────────────────────────────
    // Cancela cualquier polling en curso, libera la imagen de vista previa
    // (evita fugas de memoria) y reinicia todo el estado relacionado a IA.
    const limpiarBusquedaIA = () => {
        clearInterval(pollingRef.current);
        if (uploadedImage) URL.revokeObjectURL(uploadedImage);
        setUploadedImage(null);
        setRawFile(null);
        setSearchResult(null);
        setSimilares([]);
        setTopScore(null);
        setSelectedPlace(null);
        setIaError(null);
        setSearching(false);
        setResultadoEsIA(false);
    };

    return {
        lugares, filteredPlaces, cargando,
        selectedPlace, setSelectedPlace,
        modalPlace,    setModalPlace,
        mapCenter, setMapCenter,
        mapZoom,   setMapZoom,
        searchTerm,   setSearchTerm,
        buscarPorTexto,
        filtroPrecio, setFiltroPrecio,
        categoriasDisponibles,
        categoriasSeleccionadas,
        setCategoriasSeleccionadas,
        toggleCategoria,
        lugarSeleccionadoMenu,
        seleccionarDesdeMenu,
        limpiarFiltros,
        uploadedImage,
        searching,
        searchResult,
        resultadoEsIA,
        similares,
        topScore,
        minScoreLabel: MIN_SCORE_LABEL,
        error: iaError,
        handleImageDrop,
        ejecutarBusquedaIA,
        limpiarBusquedaIA,
    };
}