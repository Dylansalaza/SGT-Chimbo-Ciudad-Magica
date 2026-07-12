import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import StaggerGrid from '../components/StaggerGrid';
import Reveal from '../components/Reveal';
import {
  MagnifyingGlassIcon,
  XMarkIcon,
  CalendarDaysIcon,
  PhotoIcon,
  PlayIcon,
  ArrowRightIcon,
} from '@heroicons/react/24/solid';

// Base del backend Laravel, derivada de VITE_API_URL (quitando el sufijo /api).
// En producción VITE_API_URL apunta al dominio HTTPS real; en local cae al 127.0.0.1.
const LARAVEL_URL = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:3000/api').replace(/\/api$/, '');
const SERIF = "'Playfair Display', Georgia, serif";

// Detecta si una URL de portada/galería es un video (por su extensión), para
// decidir si se renderiza con <video> o con <img>.
const EXT_VIDEO = ['mp4', 'mov', 'webm'];
function esVideoUrl(url) {
    if (!url) return false;
    const ext = url.split('.').pop()?.split('?')[0]?.toLowerCase();
    return EXT_VIDEO.includes(ext);
}

// Normaliza cualquier ruta de imagen que venga del backend (relativa,
// absoluta o solo el nombre del archivo) a una URL completa y válida.
function resolverImagen(image_url) {
    if (!image_url) return null;
    const i = image_url.indexOf('/storage/');
    if (i !== -1) return LARAVEL_URL + image_url.slice(i);
    if (image_url.startsWith('http')) return image_url;
    if (image_url.startsWith('/'))    return LARAVEL_URL + image_url;
    return LARAVEL_URL + '/storage/' + image_url;
}

// Convierte cualquier fecha a 'YYYY-MM-DD' en horario LOCAL (no UTC), para
// poder comparar el rango del filtro contra el día calendario del evento.
function aDiaLocal(fecha) {
    if (!fecha) return null;
    const d = new Date(fecha);
    if (isNaN(d)) return null;
    const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 10);
}

// Formatea una fecha ISO a texto legible en español, ej: "6 de julio de 2026"
function formatearFecha(fecha) {
    if (!fecha) return 'Sin fecha';
    return new Date(fecha).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
}

// Un evento pasa a "pasado" automáticamente en cuanto su fecha de fin
// (o de inicio, si no tiene fin) queda atrás en el tiempo respecto a ahora.
function esEventoPasado(ev) {
    const ref = ev.ends_at || ev.starts_at;
    if (!ref) return false;
    const t = new Date(ref).getTime();
    return !isNaN(t) && t < Date.now();
}

// ============================================================================
// COMPONENTE PRINCIPAL: Eventos (ruta /eventos)
// Lista pública de eventos con filtros (texto, categoría, rango de fechas),
// badge de estado (🟢 actual / ⚪ pasado) calculado automáticamente según la
// fecha. Cada evento abre su propia página completa en /eventos/:id (ver
// EventoDetalle.jsx).
// ============================================================================
export default function Eventos() {
    const navigate = useNavigate();
    const [eventos, setEventos]   = useState([]); // Lista completa traída del backend
    const [cargando, setCargando] = useState(true);

    // Filtros
    const [texto, setTexto]           = useState('');
    const [categoria, setCategoria]   = useState('Todas');
    const [fechaDesde, setFechaDesde] = useState('');
    const [fechaHasta, setFechaHasta] = useState('');

    useEffect(() => { cargarEventos(); }, []);

    // Trae todos los eventos publicados desde la API pública de Laravel
    const cargarEventos = async () => {
        try {
            const response = await axios.get(`${LARAVEL_URL}/api/events`);
            setEventos(response.data.data || response.data || []);
        } catch (error) {
            console.error('Error cargando eventos:', error);
        } finally {
            setCargando(false);
        }
    };

    // Lista de categorías únicas presentes en los eventos, para el <select> de filtro
    const categorias = useMemo(() => {
        const set = new Set(eventos.map(e => e.categoria).filter(Boolean));
        return ['Todas', ...set];
    }, [eventos]);

    // Aplica los 4 filtros combinados (texto, categoría, fecha desde/hasta)
    // sobre la lista completa de eventos. Se recalcula solo cuando cambia
    // algo relevante (useMemo evita recalcular en cada render).
    const filtrados = useMemo(() => {
        // Normaliza a minúsculas y sin acentos, para que el filtro por texto
        // reconozca las categorías (y título/descripción) aunque el usuario
        // escriba sin tildes: "gastronomica" encuentra "Gastronómica".
        const norm = (s) => (s || '').normalize('NFD').replace(/\p{Mn}/gu, '').toLowerCase();
        const q = norm(texto);
        return eventos.filter(ev => {
            const t = norm(`${ev.title} ${ev.description || ''} ${ev.categoria || ''}`);
            const okTexto = !q || t.includes(q);
            const okCat   = categoria === 'Todas' || ev.categoria === categoria;
            // Coincide si el evento (su rango) se cruza con el rango del filtro
            const ini = aDiaLocal(ev.starts_at);
            const fin = aDiaLocal(ev.ends_at) || ini;
            const okDesde = !fechaDesde || (fin && fin >= fechaDesde);
            const okHasta = !fechaHasta || (ini && ini <= fechaHasta);
            return okTexto && okCat && okDesde && okHasta;
        });
    }, [eventos, texto, categoria, fechaDesde, fechaHasta]);

    const hayFiltro = texto || categoria !== 'Todas' || fechaDesde || fechaHasta;
    // Restablece todos los filtros a su valor inicial
    const limpiar = () => { setTexto(''); setCategoria('Todas'); setFechaDesde(''); setFechaHasta(''); };
    // Navega a la página completa de un evento
    const abrir = (ev) => navigate(`/eventos/${ev.id}`);

    // Nota: NO hacemos early-return al cargar. El masthead y los filtros son
    // estáticos (no dependen de datos), así que se renderizan SIEMPRE en la misma
    // posición; solo la zona de resultados muestra un skeleton mientras carga.
    // Así el contenido superior no aparece tarde empujando todo hacia abajo (CLS).
    return (
        <div className="max-w-7xl mx-auto px-4 py-8">
            <div className="max-w-5xl mx-auto" style={{ fontFamily: SERIF }}>
                {/* ===== MASTHEAD ===== */}
                <Reveal as="header" className="mb-3">
                    <div className="h-1 w-full bg-gray-900 dark:bg-gray-500 rounded-full mb-1.5" />
                    <div className="border-y-[3px] border-double border-gray-900 dark:border-gray-500 py-2 my-1 text-center">
                        <h1 className="text-gray-900 dark:text-white leading-none" style={{ fontWeight: 900, fontSize: 'clamp(2.5rem, 8vw, 5rem)', letterSpacing: '-0.02em' }}>
                            Eventos Turísticos
                        </h1>
                    </div>
                    <div className="flex items-center gap-3 text-center justify-center text-[10px] md:text-xs uppercase tracking-[0.35em] text-gray-600 dark:text-gray-400 my-1" style={{ fontFamily: "'Manrope', sans-serif" }}>
                        <span className="flex-1 border-t border-gray-400 dark:border-white/20" />
                        <span>Agenda cultural de San José de Chimbo</span>
                        <span className="flex-1 border-t border-gray-400 dark:border-white/20" />
                    </div>
                </Reveal>

                {/* ===== FILTROS (compactos) ===== */}
                <div className="flex flex-wrap items-end gap-2 mb-8 text-xs" style={{ fontFamily: "'Manrope', sans-serif" }}>
                    <div className="relative flex-1 min-w-[160px]">
                        <MagnifyingGlassIcon className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" />
                        <input type="text" value={texto} onChange={e => setTexto(e.target.value)} placeholder="Buscar..."
                            className="w-full pl-8 pr-3 py-1.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#242424] text-gray-800 dark:text-gray-100 outline-none focus:border-green-600 focus:ring-1 focus:ring-green-600 transition-colors" />
                    </div>
                    <select value={categoria} onChange={e => setCategoria(e.target.value)} className="px-3 py-1.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#242424] text-gray-800 dark:text-gray-100 outline-none focus:border-green-600 focus:ring-1 focus:ring-green-600 transition-colors">
                        {categorias.map(c => <option key={c} value={c}>{c}</option>)}
                    </select>
                    <input type="date" value={fechaDesde} onChange={e => setFechaDesde(e.target.value)} className="px-2 py-1.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#242424] text-gray-800 dark:text-gray-100 outline-none focus:border-green-600 focus:ring-1 focus:ring-green-600 transition-colors" />
                    <span className="text-gray-400">–</span>
                    <input type="date" value={fechaHasta} onChange={e => setFechaHasta(e.target.value)} className="px-2 py-1.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#242424] text-gray-800 dark:text-gray-100 outline-none focus:border-green-600 focus:ring-1 focus:ring-green-600 transition-colors" />
                    {hayFiltro && <button onClick={limpiar} className="flex items-center gap-1 px-3 py-1.5 border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600"><XMarkIcon className="w-3.5 h-3.5" /> Limpiar</button>}
                </div>
            </div>

            {cargando ? (
                /* Skeleton de la grilla: mismas dimensiones que las tarjetas
                   reales (imagen h-56 + cuerpo), para que al llegar los datos
                   no cambie la altura y no haya salto de layout. */
                <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {[...Array(6)].map((_, i) => (
                        <div key={i} className="rounded-2xl overflow-hidden ring-1 ring-black/5 dark:ring-white/10 bg-white dark:bg-[#242424]">
                            <div className="h-56 bg-gray-200 dark:bg-gray-700 animate-pulse" />
                            <div className="p-5 space-y-3">
                                <div className="h-5 w-3/4 bg-gray-200 dark:bg-gray-700 animate-pulse rounded" />
                                <div className="h-3 w-full bg-gray-200 dark:bg-gray-700 animate-pulse rounded" />
                                <div className="h-3 w-2/3 bg-gray-200 dark:bg-gray-700 animate-pulse rounded" />
                            </div>
                        </div>
                    ))}
                </div>
            ) : filtrados.length === 0 ? (
                <div className="flex flex-col items-center justify-center text-center py-16 px-6 rounded-2xl border border-dashed border-green-300/60 dark:border-green-800/60 bg-green-50/40 dark:bg-green-900/10" style={{ fontFamily: "'Manrope', sans-serif" }}>
                    <div className="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center mb-3">
                        <CalendarDaysIcon className="w-6 h-6 text-green-700 dark:text-green-400" />
                    </div>
                    <p className="text-gray-600 dark:text-gray-300 font-medium">{hayFiltro ? 'No hay eventos con esos filtros' : 'No hay eventos registrados'}</p>
                    {hayFiltro && <button onClick={limpiar} className="btn-press mt-3 text-sm font-semibold text-green-700 dark:text-green-400 hover:underline">Limpiar filtros</button>}
                </div>
            ) : (
                <StaggerGrid className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {filtrados.map((evento) => {
                        const imgSrc = resolverImagen(evento.image_url);
                        const pasado = esEventoPasado(evento);
                        return (
                            <div key={evento.id} onClick={() => abrir(evento)}
                                className="group relative bg-white dark:bg-[#242424] rounded-2xl shadow-green-md ring-1 ring-black/5 dark:ring-white/10 overflow-hidden hover:shadow-green-lg hover:-translate-y-1.5 transition-[transform,box-shadow] duration-300 ease-out cursor-pointer">
                                <div className="relative h-56 overflow-hidden bg-gray-200 dark:bg-gray-700">
                                    {imgSrc
                                        ? (esVideoUrl(evento.image_url)
                                            ? <video src={imgSrc} muted playsInline preload="metadata" className="w-full h-full object-cover" />
                                            : <img src={imgSrc} alt={evento.title} loading="lazy" decoding="async" className="w-full h-full object-cover" />)
                                        : <div className="w-full h-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"><CalendarDaysIcon className="w-12 h-12 text-gray-400 dark:text-gray-500" /></div>}
                                    {imgSrc && esVideoUrl(evento.image_url) && (
                                        <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                                            <div className="w-10 h-10 rounded-full bg-black/50 flex items-center justify-center text-white"><PlayIcon className="w-4 h-4" /></div>
                                        </div>
                                    )}
                                    <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent" />
                                    {evento.categoria && (
                                        <span className="absolute top-3 left-3 bg-green-700/95 backdrop-blur-sm text-white text-xs font-medium px-3 py-1 rounded-full ring-1 ring-inset ring-white/20">{evento.categoria}</span>
                                    )}
                                    {(evento.images?.length > 0) && (
                                        <span className="absolute top-3 right-3 flex items-center gap-1 bg-black/60 text-white text-xs px-2 py-0.5 rounded-full"><PhotoIcon className="w-3.5 h-3.5" /> {evento.images.length + 1}</span>
                                    )}
                                    <span className="absolute bottom-3 left-3 flex items-center gap-1 bg-black/60 backdrop-blur-sm text-white text-xs px-3 py-1 rounded-full"><CalendarDaysIcon className="w-3.5 h-3.5" /> {formatearFecha(evento.starts_at)}</span>
                                    <span className={`absolute bottom-3 right-3 flex items-center gap-1 backdrop-blur-sm text-white text-xs px-3 py-1 rounded-full font-semibold ${pasado ? 'bg-gray-600/80' : 'bg-emerald-500/90'}`}>
                                        <span className="w-1.5 h-1.5 rounded-full bg-current" /> {pasado ? 'Evento pasado' : 'Evento actual'}
                                    </span>
                                </div>
                                <div className="p-5">
                                    <h2 className="text-xl font-bold mb-2 line-clamp-2 text-gray-800 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors">{evento.title}</h2>
                                    {evento.description && (
                                        <p className="text-gray-600 dark:text-gray-300 text-sm line-clamp-3 mb-3">{evento.description.substring(0, 150)}…</p>
                                    )}
                                    <span className="mt-2 text-green-700 dark:text-green-400 text-sm font-semibold flex items-center gap-1.5 group-hover:gap-2.5 transition-[gap] duration-200 ease-out">Ver detalles <ArrowRightIcon className="w-4 h-4" /></span>
                                </div>
                            </div>
                        );
                    })}
                </StaggerGrid>
            )}

        </div>
    );
}
       