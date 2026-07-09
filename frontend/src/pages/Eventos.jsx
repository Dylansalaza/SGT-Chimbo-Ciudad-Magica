import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import {
  MagnifyingGlassIcon,
  XMarkIcon,
  CalendarDaysIcon,
  PhotoIcon,
  PlayIcon,
  ArrowRightIcon,
  FlagIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/react/24/solid';

const LARAVEL_URL = 'http://127.0.0.1:3000';
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

// Arma el array de imágenes/videos de un evento (portada + galería adicional)
// ya resueltas a URL completa, para alimentar el carrusel del modal.
function galeriaDe(ev) {
    const imgs = [];
    if (ev.image_url) imgs.push(resolverImagen(ev.image_url));
    (ev.images || []).forEach(u => { const r = resolverImagen(u); if (r) imgs.push(r); });
    return imgs;
}

// ============================================================================
// COMPONENTE PRINCIPAL: Eventos (ruta /eventos)
// Lista pública de eventos con filtros (texto, categoría, rango de fechas),
// badge de estado (🟢 actual / ⚪ pasado) calculado automáticamente según la
// fecha, y un modal de detalle con carrusel de fotos/videos.
// ============================================================================
export default function Eventos() {
    const [eventos, setEventos]                 = useState([]); // Lista completa traída del backend
    const [cargando, setCargando]               = useState(true);
    const [eventoSeleccionado, setSeleccionado] = useState(null); // Evento abierto en el modal
    const [imgActiva, setImgActiva]             = useState(0);    // Índice de la imagen activa en el carrusel del modal

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
        return eventos.filter(ev => {
            const t = `${ev.title} ${ev.description || ''}`.toLowerCase();
            const okTexto = !texto || t.includes(texto.toLowerCase());
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
    // Abre el modal de detalle de un evento, reiniciando el carrusel en la primera imagen
    const abrir = (ev) => { setSeleccionado(ev); setImgActiva(0); };

    if (cargando) {
        return (
            <div className="flex justify-center items-center h-64">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto" />
                    <p className="mt-4 text-gray-500">Cargando eventos...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="max-w-7xl mx-auto px-4 py-8">
            <div className="max-w-5xl mx-auto" style={{ fontFamily: SERIF }}>
                {/* ===== MASTHEAD ===== */}
                <header className="mb-3">
                    <div className="border-y-[3px] border-double border-gray-900 dark:border-gray-500 py-2 my-1 text-center">
                        <h1 className="text-gray-900 dark:text-white leading-none" style={{ fontWeight: 900, fontSize: 'clamp(2.5rem, 8vw, 5rem)', letterSpacing: '-0.02em' }}>
                            Eventos Turísticos
                        </h1>
                    </div>
                    <div className="flex items-center gap-3 text-center justify-center text-[10px] md:text-xs uppercase tracking-[0.35em] text-gray-600 dark:text-gray-400 my-1" style={{ fontFamily: "'Manrope', sans-serif" }}>
                        <span className="flex-1 border-t border-gray-400 dark:border-gray-600" />
                        <span>Agenda cultural de San José de Chimbo</span>
                        <span className="flex-1 border-t border-gray-400 dark:border-gray-600" />
                    </div>
                </header>

                {/* ===== FILTROS (compactos) ===== */}
                <div className="flex flex-wrap items-end gap-2 mb-8 text-xs" style={{ fontFamily: "'Manrope', sans-serif" }}>
                    <div className="relative flex-1 min-w-[160px]">
                        <MagnifyingGlassIcon className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" />
                        <input type="text" value={texto} onChange={e => setTexto(e.target.value)} placeholder="Buscar..."
                            className="w-full pl-8 pr-3 py-1.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#242424] text-gray-800 dark:text-gray-100" />
                    </div>
                    <select value={categoria} onChange={e => setCategoria(e.target.value)} className="px-3 py-1.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#242424] text-gray-800 dark:text-gray-100">
                        {categorias.map(c => <option key={c} value={c}>{c}</option>)}
                    </select>
                    <input type="date" value={fechaDesde} onChange={e => setFechaDesde(e.target.value)} className="px-2 py-1.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#242424] text-gray-800 dark:text-gray-100" />
                    <span className="text-gray-400">–</span>
                    <input type="date" value={fechaHasta} onChange={e => setFechaHasta(e.target.value)} className="px-2 py-1.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#242424] text-gray-800 dark:text-gray-100" />
                    {hayFiltro && <button onClick={limpiar} className="flex items-center gap-1 px-3 py-1.5 border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600"><XMarkIcon className="w-3.5 h-3.5" /> Limpiar</button>}
                </div>
            </div>

            {filtrados.length === 0 ? (
                <div className="text-center py-12">
                    <p className="text-gray-500 text-lg">{hayFiltro ? 'No hay eventos con esos filtros' : 'No hay eventos registrados'}</p>
                </div>
            ) : (
                <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {filtrados.map((evento) => {
                        const imgSrc = resolverImagen(evento.image_url);
                        const pasado = esEventoPasado(evento);
                        return (
                            <div key={evento.id} onClick={() => abrir(evento)}
                                className="group relative bg-white dark:bg-[#242424] rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer">
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
                                        <span className="absolute top-3 left-3 bg-blue-600/90 text-white text-xs px-3 py-1 rounded-full">{evento.categoria}</span>
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
                                    <h2 className="text-xl font-bold mb-2 line-clamp-2 text-gray-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{evento.title}</h2>
                                    {evento.description && (
                                        <p className="text-gray-600 dark:text-gray-300 text-sm line-clamp-3 mb-3">{evento.description.substring(0, 150)}…</p>
                                    )}
                                    <span className="mt-2 text-blue-500 text-sm font-medium flex items-center gap-1 group-hover:gap-2 transition-all">Ver detalles <ArrowRightIcon className="w-4 h-4" /></span>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Modal con carrusel */}
            {eventoSeleccionado && (() => {
                const imgs = galeriaDe(eventoSeleccionado);
                const prev = (e) => { e.stopPropagation(); setImgActiva(i => (i - 1 + imgs.length) % imgs.length); };
                const next = (e) => { e.stopPropagation(); setImgActiva(i => (i + 1) % imgs.length); };
                return (
                    <div className="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4" onClick={() => setSeleccionado(null)}>
                        <div className="relative max-w-4xl w-full bg-white dark:bg-[#242424] rounded-2xl flex flex-col max-h-[92vh] my-4" onClick={e => e.stopPropagation()}>
                            <button onClick={() => setSeleccionado(null)} className="absolute top-4 right-4 z-20 bg-black/50 hover:bg-black/70 text-white rounded-full w-10 h-10 flex items-center justify-center"><XMarkIcon className="w-5 h-5" /></button>

                            {/* Carrusel principal */}
                            <div className="relative h-64 md:h-80 bg-gray-900 overflow-hidden select-none rounded-t-2xl mx-2 mt-2 shrink-0">
                                {imgs.length > 0 ? (
                                    imgs.map((src, idx) => (
                                        esVideoUrl(src) ? (
                                            <video key={idx} src={src} controls={idx === imgActiva} muted playsInline preload="metadata"
                                                className={`absolute inset-0 w-full h-full object-cover transition-opacity duration-500 ${idx === imgActiva ? 'opacity-100' : 'opacity-0 pointer-events-none'}`} />
                                        ) : (
                                            <img key={idx} src={src} alt={`${eventoSeleccionado.title} ${idx + 1}`}
                                                className={`absolute inset-0 w-full h-full object-cover transition-opacity duration-500 ${idx === imgActiva ? 'opacity-100' : 'opacity-0 pointer-events-none'}`} />
                                        )
                                    ))
                                ) : (
                                    <div className="w-full h-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"><CalendarDaysIcon className="w-16 h-16 text-gray-400 dark:text-gray-500" /></div>
                                )}

                                <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent pointer-events-none" />

                                {/* Flechas */}
                                {imgs.length > 1 && (
                                    <>
                                        <button onClick={prev}
                                            className="absolute left-3 top-1/2 -translate-y-1/2 z-10 bg-black/50 hover:bg-black/75 text-white rounded-full w-10 h-10 flex items-center justify-center transition-all hover:scale-110 backdrop-blur-sm">
                                            <ChevronLeftIcon className="w-5 h-5" />
                                        </button>
                                        <button onClick={next}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 z-10 bg-black/50 hover:bg-black/75 text-white rounded-full w-10 h-10 flex items-center justify-center transition-all hover:scale-110 backdrop-blur-sm">
                                            <ChevronRightIcon className="w-5 h-5" />
                                        </button>
                                        {/* Puntos indicadores */}
                                        <div className="absolute bottom-14 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
                                            {imgs.map((_, idx) => (
                                                <button key={idx} onClick={e => { e.stopPropagation(); setImgActiva(idx); }}
                                                    className={`rounded-full transition-all duration-300 ${idx === imgActiva ? 'bg-white w-4 h-2' : 'bg-white/50 w-2 h-2 hover:bg-white/80'}`} />
                                            ))}
                                        </div>
                                        {/* Contador */}
                                        <span className="absolute top-4 left-4 z-10 bg-black/50 text-white text-xs px-2 py-1 rounded-full backdrop-blur-sm">
                                            {imgActiva + 1} / {imgs.length}
                                        </span>
                                    </>
                                )}

                                <div className="absolute bottom-4 left-4 flex flex-wrap gap-2 z-10">
                                    {eventoSeleccionado.categoria && <span className="text-sm bg-blue-600/80 text-white px-3 py-1 rounded-full">{eventoSeleccionado.categoria}</span>}
                                    <span className="flex items-center gap-1 text-sm bg-black/50 text-white px-3 py-1 rounded-full"><CalendarDaysIcon className="w-4 h-4" /> Inicio: {formatearFecha(eventoSeleccionado.starts_at)}</span>
                                    {eventoSeleccionado.ends_at && <span className="flex items-center gap-1 text-sm bg-blue-500/70 text-white px-3 py-1 rounded-full"><FlagIcon className="w-4 h-4" /> Fin: {formatearFecha(eventoSeleccionado.ends_at)}</span>}
                                    <span className={`flex items-center gap-1 text-sm text-white px-3 py-1 rounded-full font-semibold ${esEventoPasado(eventoSeleccionado) ? 'bg-gray-600/80' : 'bg-emerald-500/90'}`}>
                                        <span className="w-1.5 h-1.5 rounded-full bg-current" /> {esEventoPasado(eventoSeleccionado) ? 'Evento pasado' : 'Evento actual'}
                                    </span>
                                </div>
                            </div>

                            {/* Miniaturas */}
                            {imgs.length > 1 && (
                                <div className="flex gap-2 px-4 py-2 overflow-x-auto bg-gray-100 dark:bg-[#242424] shrink-0 mx-2">
                                    {imgs.map((src, idx) => (
                                        <div key={idx} onClick={() => setImgActiva(idx)}
                                            className={`relative h-16 w-24 shrink-0 rounded-lg cursor-pointer border-2 transition-all overflow-hidden ${idx === imgActiva ? 'border-blue-500 scale-105' : 'border-transparent opacity-60 hover:opacity-100'}`}>
                                            {esVideoUrl(src) ? (
                                                <>
                                                    <video src={src} muted playsInline preload="metadata" className="h-full w-full object-cover" />
                                                    <span className="absolute inset-0 flex items-center justify-center text-white bg-black/30"><PlayIcon className="w-4 h-4" /></span>
                                                </>
                                            ) : (
                                                <img src={src} className="h-full w-full object-cover" />
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Texto — ocupa el espacio restante y hace scroll */}
                            <div className="flex-1 overflow-y-auto min-h-0">
                                <div className="px-8 py-5 mx-auto max-w-3xl">
                                    <h2 className="text-2xl font-bold mb-3 text-gray-800 dark:text-white">{eventoSeleccionado.title}</h2>
                                    <div className="text-gray-600 dark:text-gray-300 text-sm leading-7 whitespace-pre-wrap text-justify pb-4">
                                        {eventoSeleccionado.description || 'Sin descripción disponible.'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                );
            })()}
        </div>
    );
}
       