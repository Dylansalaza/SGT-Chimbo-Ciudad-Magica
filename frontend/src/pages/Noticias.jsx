import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import Reveal from '../components/Reveal';
import StaggerGrid from '../components/StaggerGrid';
import {
  MagnifyingGlassIcon,
  XMarkIcon,
  ArrowRightIcon,
  PlayCircleIcon,
} from '@heroicons/react/24/solid';

// Base del backend Laravel, derivada de VITE_API_URL (quitando el sufijo /api).
// En producción VITE_API_URL apunta al dominio HTTPS real; en local cae al 127.0.0.1.
const LARAVEL_URL = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:3000/api').replace(/\/api$/, '');
const SERIF = "'Playfair Display', Georgia, serif";

// Normaliza cualquier ruta de imagen que venga del backend a una URL completa
function resolverImagen(image_url) {
    if (!image_url) return null;
    const i = image_url.indexOf('/storage/');
    if (i !== -1) return LARAVEL_URL + image_url.slice(i);
    if (image_url.startsWith('http')) return image_url;
    if (image_url.startsWith('/'))    return LARAVEL_URL + image_url;
    return LARAVEL_URL + '/storage/' + image_url;
}

// Detecta si una URL corresponde a un video (portada de noticia en video).
function esVideo(url) {
    return /\.(mp4|webm|ogg|mov|m4v)(\?|$)/i.test(url || '');
}

// Convierte cualquier fecha a 'YYYY-MM-DD' en horario LOCAL (no UTC), para
// comparar solo el día calendario (filtros de fecha y estado actual/pasada).
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

// Una noticia se considera "actual" solo el día en que fue publicada;
// al día siguiente pasa automáticamente a "noticia pasada" (estilo periódico del día).
function esNoticiaPasada(n) {
    const dia = aDiaLocal(n.published_at);
    const hoy = aDiaLocal(new Date());
    if (!dia || !hoy) return false;
    return dia < hoy;
}

// ============================================================================
// COMPONENTE PRINCIPAL: Noticias (ruta /noticias)
// Boletín estilo periódico: la noticia más reciente se muestra como nota
// principal (destacada) y el resto en columnas tipo diario. Incluye filtros
// (texto, categoría, rango de fechas), badge de estado (🟢 actual el día que
// se publicó / ⚪ pasada al día siguiente). Cada noticia abre su propia
// página completa en /noticias/:id (ver NoticiaDetalle.jsx).
// ============================================================================
export default function Noticias() {
    const navigate = useNavigate();
    const [noticias, setNoticias] = useState([]); // Lista completa traída del backend
    const [cargando, setCargando] = useState(true);

    // Filtros
    const [texto, setTexto]           = useState('');
    const [categoria, setCategoria]   = useState('Todas');
    const [fechaDesde, setFechaDesde] = useState('');
    const [fechaHasta, setFechaHasta] = useState('');

    useEffect(() => { cargarNoticias(); }, []);

    // Trae todas las noticias publicadas desde la API pública de Laravel
    const cargarNoticias = async () => {
        try {
            const response = await axios.get(`${LARAVEL_URL}/api/news`);
            setNoticias(response.data.data || response.data || []);
        } catch (error) {
            console.error('Error cargando noticias:', error);
        } finally {
            setCargando(false);
        }
    };

    // Lista de categorías únicas presentes en las noticias, para el <select> de filtro
    const categorias = useMemo(() => {
        const set = new Set(noticias.map(n => n.categoria).filter(Boolean));
        return ['Todas', ...set];
    }, [noticias]);

    // Aplica los filtros combinados (texto, categoría, fecha desde/hasta)
    // sobre la lista completa de noticias.
    const filtradas = useMemo(() => {
        // Normaliza a minúsculas y sin acentos, para que el filtro por texto
        // reconozca las categorías (y títulos/cuerpo) aunque el usuario escriba
        // sin tildes: "gastronomica" encuentra "Gastronómica".
        const norm = (s) => (s || '').normalize('NFD').replace(/\p{Mn}/gu, '').toLowerCase();
        const q = norm(texto);
        return noticias.filter(n => {
            const t = norm(`${n.title} ${n.body || ''} ${n.categoria || ''}`);
            const okTexto = !q || t.includes(q);
            const okCat   = categoria === 'Todas' || n.categoria === categoria;
            const dia     = aDiaLocal(n.published_at);
            const okDesde = !fechaDesde || (dia && dia >= fechaDesde);
            const okHasta = !fechaHasta || (dia && dia <= fechaHasta);
            return okTexto && okCat && okDesde && okHasta;
        });
    }, [noticias, texto, categoria, fechaDesde, fechaHasta]);

    const hayFiltro = texto || categoria !== 'Todas' || fechaDesde || fechaHasta;
    // Restablece todos los filtros a su valor inicial
    const limpiar = () => { setTexto(''); setCategoria('Todas'); setFechaDesde(''); setFechaHasta(''); };
    // Navega a la página completa de lectura de una noticia
    const abrir = (n) => navigate(`/noticias/${n.id}`);

    // Mientras carga mostramos un "esqueleto" con la MISMA forma del periódico
    // (masthead + nota principal + columnas), para que no haya salto de layout
    // ni el antiguo spinner con texto "Cargando noticias...".
    if (cargando) {
        const barra = 'bg-gray-200/80 dark:bg-gray-700/60 animate-pulse rounded';
        return (
            <div>
                <div className="max-w-7xl mx-auto px-4 py-8" style={{ fontFamily: SERIF }}>
                    {/* Masthead */}
                    <div className="h-1 w-full bg-gray-900 dark:bg-gray-500 rounded-full mb-1.5" />
                    <div className="border-y-[3px] border-double border-gray-900/30 dark:border-gray-500/30 py-4 my-1 flex justify-center">
                        <div className={`h-12 md:h-16 w-2/3 max-w-md ${barra}`} />
                    </div>
                    <div className="flex items-center gap-3 justify-center my-3">
                        <span className="flex-1 border-t border-gray-300 dark:border-white/20" />
                        <div className={`h-3 w-56 max-w-[60%] ${barra}`} />
                        <span className="flex-1 border-t border-gray-300 dark:border-white/20" />
                    </div>

                    {/* Nota principal */}
                    <div className="border-b-2 border-green-800/20 dark:border-green-700/20 pb-6 mb-6 mt-6">
                        <div className={`h-9 md:h-11 w-3/4 mx-auto mb-4 ${barra}`} />
                        <div className="md:float-left md:w-1/2 md:mr-6 mb-3">
                            <div className={`w-full h-64 ${barra}`} />
                        </div>
                        <div className="space-y-3">
                            {[90, 82, 74, 66, 58].map((w, i) => (
                                <div key={i} className={`h-3.5 ${barra}`} style={{ width: `${w}%` }} />
                            ))}
                        </div>
                        <div className="clear-both" />
                    </div>

                    {/* Columnas de periódico */}
                    <div className="columns-1 sm:columns-2 lg:columns-3" style={{ columnGap: '2rem' }}>
                        {[...Array(6)].map((_, i) => (
                            <div key={i} className="break-inside-avoid mb-5 pb-5 border-b border-gray-200 dark:border-gray-700">
                                <div className={`h-5 w-5/6 mb-2 ${barra}`} />
                                <div className={`w-full h-32 mb-2 ${barra}`} />
                                <div className="space-y-2">
                                    <div className={`h-3 w-full ${barra}`} />
                                    <div className={`h-3 w-11/12 ${barra}`} />
                                    <div className={`h-3 w-2/3 ${barra}`} />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    }

    const principal = filtradas[0];
    const resto = filtradas.slice(1);

    return (
        <div>
        <div className="max-w-7xl mx-auto px-4 py-8" style={{ fontFamily: SERIF }}>

            {/* ===== MASTHEAD ===== */}
            <Reveal as="header" className="mb-3">
                <div className="h-1 w-full bg-gray-900 dark:bg-gray-500 rounded-full mb-1.5" />
                <div className="border-y-[3px] border-double border-gray-900 dark:border-gray-500 py-2 my-1 text-center">
                    <h1 className="text-gray-900 dark:text-white leading-none" style={{ fontWeight: 900, fontSize: 'clamp(2.5rem, 8vw, 5rem)', letterSpacing: '-0.02em' }}>
                        Noticias del Día
                    </h1>
                </div>
                <div className="flex items-center gap-3 text-center justify-center text-[10px] md:text-xs uppercase tracking-[0.35em] text-gray-600 dark:text-gray-400 my-1" style={{ fontFamily: "'Manrope', sans-serif" }}>
                    <span className="flex-1 border-t border-gray-400 dark:border-white/20" />
                    <span>Boletín informativo de San José de Chimbo</span>
                    <span className="flex-1 border-t border-gray-400 dark:border-white/20" />
                </div>
            </Reveal>

            {/* ===== FILTROS (compactos) ===== */}
            <div className="flex flex-wrap items-end gap-2 mb-6 text-xs" style={{ fontFamily: "'Manrope', sans-serif" }}>
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

            {filtradas.length === 0 ? (
                <div className="text-center py-16 border-y border-gray-300 dark:border-white/20">
                    <p className="text-gray-500 dark:text-gray-400 text-lg">{hayFiltro ? 'No hay noticias con esos filtros' : 'No hay noticias disponibles'}</p>
                </div>
            ) : (
                <>
                    {/* ===== NOTA PRINCIPAL ===== */}
                    <article onClick={() => abrir(principal)} className="animate-fade-in-up cursor-pointer border-b-2 border-green-800 dark:border-green-700 pb-6 mb-6 group">
                        {principal.categoria && (
                            <p className="text-center text-[11px] uppercase tracking-[0.3em] text-gray-500 dark:text-gray-400 mb-1" style={{ fontFamily: "'Manrope', sans-serif" }}>{principal.categoria}</p>
                        )}
                        <h2 className="text-center font-black text-gray-900 dark:text-white leading-tight group-hover:text-gray-700 dark:group-hover:text-gray-300" style={{ fontSize: 'clamp(1.8rem, 4vw, 3rem)' }}>
                            {principal.title}
                        </h2>
                        <p className="flex items-center justify-center gap-2 text-center text-xs text-gray-500 dark:text-gray-400 italic mt-1 mb-4">
                            <span>{formatearFecha(principal.published_at)}</span>
                            <span className={`flex items-center gap-1 not-italic font-semibold px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wide ${esNoticiaPasada(principal) ? 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'}`}>
                                <span className="w-1.5 h-1.5 rounded-full bg-current" /> {esNoticiaPasada(principal) ? 'Noticia pasada' : 'Noticia actual'}
                            </span>
                        </p>

                        {resolverImagen(principal.image_url) && (
                            <div className="float-none md:float-left md:w-1/2 md:mr-6 mb-3">
                                {esVideo(principal.image_url) ? (
                                    <video src={resolverImagen(principal.image_url)} controls playsInline preload="metadata" className="w-full h-64 object-contain bg-black border border-gray-300 dark:border-gray-600" />
                                ) : (
                                    <img src={resolverImagen(principal.image_url)} alt={principal.title} loading="eager" className="w-full h-64 object-cover border border-gray-300 dark:border-gray-600" />
                                )}
                                <p className="text-[11px] text-gray-500 dark:text-gray-400 italic mt-1">{principal.title}</p>
                            </div>
                        )}
                        <div className="text-justify text-gray-800 dark:text-gray-300 leading-relaxed text-[15px] first-letter:text-6xl first-letter:font-black first-letter:mr-2 first-letter:float-left first-letter:leading-[0.8]">
                            {(principal.body || '').substring(0, 700)}…
                        </div>
                        <div className="clear-both" />
                        <button
                            type="button"
                            onClick={(e) => { e.stopPropagation(); abrir(principal); }}
                            className="btn-press group mt-3 inline-flex items-center gap-1.5 px-4 py-2 bg-green-700 text-white text-sm font-semibold hover:bg-green-800 shadow-green-md"
                            style={{ fontFamily: "'Manrope', sans-serif" }}
                        >
                            Ver más información <ArrowRightIcon className="inline w-3.5 h-3.5 transition-transform duration-200 ease-out group-hover:translate-x-0.5" />
                        </button>
                    </article>

                    {/* ===== RESTO EN COLUMNAS DE PERIÓDICO ===== */}
                    {resto.length > 0 && (
                        <StaggerGrid style={{ columnGap: '2rem', columnRule: '1px solid #cbd5e1' }} className="columns-1 sm:columns-2 lg:columns-3">
                            {resto.map(n => (
                                <article key={n.id} onClick={() => abrir(n)}
                                    className="break-inside-avoid mb-5 pb-5 border-b border-gray-300 dark:border-white/20 cursor-pointer group">
                                    {n.categoria && (
                                        <p className="text-[10px] uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-0.5" style={{ fontFamily: "'Manrope', sans-serif" }}>{n.categoria}</p>
                                    )}
                                    <h3 className="font-bold text-gray-900 dark:text-white leading-snug text-lg group-hover:text-gray-600 dark:group-hover:text-gray-300">{n.title}</h3>
                                    <p className="flex items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400 italic mb-1.5">
                                        <span>{formatearFecha(n.published_at)}</span>
                                        <span className={`flex items-center gap-1 not-italic font-semibold px-1.5 py-0.5 rounded-full text-[9px] uppercase tracking-wide ${esNoticiaPasada(n) ? 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'}`}>
                                            <span className="w-1.5 h-1.5 rounded-full bg-current" /> {esNoticiaPasada(n) ? 'Pasada' : 'Actual'}
                                        </span>
                                    </p>
                                    {resolverImagen(n.image_url) && (
                                        esVideo(n.image_url) ? (
                                            <div className="relative w-full h-32 mb-2 border border-gray-300 dark:border-gray-600 overflow-hidden bg-black">
                                                <video src={resolverImagen(n.image_url)} muted playsInline preload="metadata" className="w-full h-full object-cover" />
                                                <span className="absolute inset-0 flex items-center justify-center pointer-events-none">
                                                    <PlayCircleIcon className="w-10 h-10 text-white/90 drop-shadow" />
                                                </span>
                                            </div>
                                        ) : (
                                            <img src={resolverImagen(n.image_url)} alt={n.title} loading="lazy" decoding="async" className="w-full h-32 object-cover border border-gray-300 dark:border-gray-600 mb-2" />
                                        )
                                    )}
                                    <p className="text-justify text-gray-700 dark:text-gray-300 text-[13.5px] leading-relaxed">{(n.body || '').substring(0, 220)}…</p>
                                    <button
                                        type="button"
                                        onClick={(e) => { e.stopPropagation(); abrir(n); }}
                                        className="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100 underline hover:text-gray-600 dark:hover:text-gray-300"
                                        style={{ fontFamily: "'Manrope', sans-serif" }}
                                    >
                                        Ver más información <ArrowRightIcon className="inline w-3.5 h-3.5" />
                                    </button>
                                </article>
                            ))}
                        </StaggerGrid>
                    )}
                </>
            )}
        </div>
        </div>
    );
}