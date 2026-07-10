import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import {
  MagnifyingGlassIcon,
  XMarkIcon,
  ArrowRightIcon,
} from '@heroicons/react/24/solid';

const LARAVEL_URL = 'http://127.0.0.1:3000';
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
        return noticias.filter(n => {
            const t = `${n.title} ${n.body || ''}`.toLowerCase();
            const okTexto = !texto || t.includes(texto.toLowerCase());
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

    if (cargando) {
        return (
            <div className="flex justify-center items-center h-64">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-800 dark:border-gray-300 mx-auto" />
                    <p className="mt-4 text-gray-500 dark:text-gray-400">Cargando noticias...</p>
                </div>
            </div>
        );
    }

    const principal = filtradas[0];
    const resto = filtradas.slice(1);

    return (
        <div className="bg-[#fcfbf7] dark:bg-[#242424] transition-colors">
        <div className="max-w-7xl mx-auto px-4 py-8" style={{ fontFamily: SERIF }}>

            {/* ===== MASTHEAD ===== */}
            <header className="mb-3">
                <div className="border-y-[3px] border-double border-gray-900 dark:border-gray-500 py-2 my-1 text-center">
                    <h1 className="text-gray-900 dark:text-white leading-none" style={{ fontWeight: 900, fontSize: 'clamp(2.5rem, 8vw, 5rem)', letterSpacing: '-0.02em' }}>
                        Noticias del Día
                    </h1>
                </div>
                <div className="flex items-center gap-3 text-center justify-center text-[10px] md:text-xs uppercase tracking-[0.35em] text-gray-600 dark:text-gray-400 my-1" style={{ fontFamily: "'Manrope', sans-serif" }}>
                    <span className="flex-1 border-t border-gray-400 dark:border-gray-600" />
                    <span>Boletín informativo de San José de Chimbo</span>
                    <span className="flex-1 border-t border-gray-400 dark:border-gray-600" />
                </div>
            </header>

            {/* ===== FILTROS (compactos) ===== */}
            <div className="flex flex-wrap items-end gap-2 mb-6 text-xs" style={{ fontFamily: "'Manrope', sans-serif" }}>
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

            {filtradas.length === 0 ? (
                <div className="text-center py-16 border-y border-gray-300 dark:border-gray-700">
                    <p className="text-gray-500 dark:text-gray-400 text-lg">{hayFiltro ? 'No hay noticias con esos filtros' : 'No hay noticias disponibles'}</p>
                </div>
            ) : (
                <>
                    {/* ===== NOTA PRINCIPAL ===== */}
                    <article onClick={() => abrir(principal)} className="cursor-pointer border-b-2 border-gray-900 dark:border-gray-600 pb-6 mb-6 group">
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
                                <img src={resolverImagen(principal.image_url)} alt={principal.title} loading="eager" className="w-full h-64 object-cover border border-gray-300 dark:border-gray-600" />
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
                            className="mt-3 inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 dark:bg-gray-700 text-white text-sm font-semibold hover:bg-gray-700 dark:hover:bg-gray-600 transition-colors"
                            style={{ fontFamily: "'Manrope', sans-serif" }}
                        >
                            Ver más información <ArrowRightIcon className="inline w-3.5 h-3.5" />
                        </button>
                    </article>

                    {/* ===== RESTO EN COLUMNAS DE PERIÓDICO ===== */}
                    {resto.length > 0 && (
                        <div style={{ columnGap: '2rem', columnRule: '1px solid #cbd5e1' }} className="columns-1 sm:columns-2 lg:columns-3">
                            {resto.map(n => (
                                <article key={n.id} onClick={() => abrir(n)}
                                    className="break-inside-avoid mb-5 pb-5 border-b border-gray-300 dark:border-gray-700 cursor-pointer group">
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
                                        <img src={resolverImagen(n.image_url)} alt={n.title} loading="lazy" decoding="async" className="w-full h-32 object-cover border border-gray-300 dark:border-gray-600 mb-2" />
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
                        </div>
                    )}
                </>
            )}
        </div>
        </div>
    );
}