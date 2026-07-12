import { useEffect, useMemo, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import axios from 'axios';
import StaggerGrid from '../components/StaggerGrid';
import {
  ArrowLeftIcon,
  ArrowRightIcon,
  CalendarDaysIcon,
  FlagIcon,
  PhotoIcon,
  PlayIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/react/24/solid';

// Base del backend Laravel, derivada de VITE_API_URL (quitando el sufijo /api).
// En producción VITE_API_URL apunta al dominio HTTPS real; en local cae al 127.0.0.1.
const LARAVEL_URL = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:3000/api').replace('/api', '');
const SERIF = "'Playfair Display', Georgia, serif";

// Detecta si una URL de portada/galería es un video (por su extensión)
const EXT_VIDEO = ['mp4', 'mov', 'webm'];
function esVideoUrl(url) {
    if (!url) return false;
    const ext = url.split('.').pop()?.split('?')[0]?.toLowerCase();
    return EXT_VIDEO.includes(ext);
}

// Normaliza cualquier ruta de imagen que venga del backend a una URL completa
function resolverImagen(image_url) {
    if (!image_url) return null;
    const i = image_url.indexOf('/storage/');
    if (i !== -1) return LARAVEL_URL + image_url.slice(i);
    if (image_url.startsWith('http')) return image_url;
    if (image_url.startsWith('/'))    return LARAVEL_URL + image_url;
    return LARAVEL_URL + '/storage/' + image_url;
}

// Formatea una fecha ISO a texto legible en español, ej: "6 de julio de 2026"
function formatearFecha(fecha) {
    if (!fecha) return 'Sin fecha';
    return new Date(fecha).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
}

// Un evento pasa a "pasado" en cuanto su fecha de fin (o inicio) queda atrás
function esEventoPasado(ev) {
    const ref = ev.ends_at || ev.starts_at;
    if (!ref) return false;
    const t = new Date(ref).getTime();
    return !isNaN(t) && t < Date.now();
}

// Arma el array de imágenes/videos de un evento (portada + galería adicional)
function galeriaDe(ev) {
    const imgs = [];
    if (ev.image_url) imgs.push(resolverImagen(ev.image_url));
    (ev.images || []).forEach(u => { const r = resolverImagen(u); if (r) imgs.push(r); });
    return imgs;
}

// Mezcla un array (Fisher-Yates) sin mutar el original, para que "Otros
// eventos" muestre una selección distinta cada vez en lugar de siempre
// los primeros del listado.
function mezclar(arr) {
    const copia = [...arr];
    for (let i = copia.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [copia[i], copia[j]] = [copia[j], copia[i]];
    }
    return copia;
}

// ============================================================================
// COMPONENTE: EventoDetalle (ruta /eventos/:id)
// Página completa de un evento individual (ya no un modal sobre la lista).
// Incluye galería de fotos/videos con carrusel, descripción completa y, al
// final, una sección "Otros eventos" para seguir navegando sin volver atrás.
// ============================================================================
export default function EventoDetalle() {
    const { id } = useParams();
    const navigate = useNavigate();

    const [evento, setEvento]       = useState(null);
    const [otros, setOtros]         = useState([]);
    const [cargando, setCargando]   = useState(true);
    const [error, setError]         = useState(false);
    const [imgActiva, setImgActiva] = useState(0);

    useEffect(() => {
        setCargando(true);
        setError(false);
        setImgActiva(0);
        window.scrollTo({ top: 0, left: 0, behavior: 'instant' });

        axios.get(`${LARAVEL_URL}/api/events/${id}`)
            .then(res => setEvento(res.data.data || res.data))
            .catch(() => setError(true))
            .finally(() => setCargando(false));

        axios.get(`${LARAVEL_URL}/api/events`)
            .then(res => setOtros(res.data.data || res.data || []))
            .catch(() => {});
    }, [id]);

    // Selección aleatoria de "Otros eventos" (se remezcla solo al cambiar
    // de evento o al llegar la lista, no en cada render).
    const sugeridos = useMemo(
        () => mezclar(otros.filter(e => e.id !== Number(id))).slice(0, 3),
        [otros, id]
    );

    if (cargando) {
        return (
            <div className="flex justify-center items-center h-64">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-2 border-green-200 border-t-green-600 mx-auto" />
                    <p className="mt-4 text-gray-500 dark:text-gray-400">Cargando evento…</p>
                </div>
            </div>
        );
    }

    if (error || !evento) {
        return (
            <div className="max-w-2xl mx-auto px-4 py-20 text-center" style={{ fontFamily: SERIF }}>
                <h1 className="text-3xl font-black text-gray-900 dark:text-white mb-3">Evento no encontrado</h1>
                <p className="text-gray-500 dark:text-gray-400 mb-6">Puede que haya sido retirado o el enlace sea incorrecto.</p>
                <Link to="/eventos" className="btn-press inline-flex items-center gap-1.5 px-5 py-2.5 bg-green-700 text-white rounded-full font-semibold text-sm hover:bg-green-800 shadow-green-md">
                    <ArrowLeftIcon className="w-4 h-4" /> Volver a Eventos
                </Link>
            </div>
        );
    }

    const imgs = galeriaDe(evento);
    const prev = () => setImgActiva(i => (i - 1 + imgs.length) % imgs.length);
    const next = () => setImgActiva(i => (i + 1) % imgs.length);
    const pasado = esEventoPasado(evento);

    return (
        <div className="max-w-7xl mx-auto px-4 py-8" style={{ fontFamily: SERIF }}>

            {/* ===== Volver ===== */}
            <button
                onClick={() => navigate('/eventos')}
                className="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white mb-6 transition-colors"
                style={{ fontFamily: "'Manrope', sans-serif" }}
            >
                <ArrowLeftIcon className="w-4 h-4" /> Volver a Eventos
            </button>

            <article className="animate-fade-in-up">
                {evento.categoria && (
                    <p className="text-center text-[11px] uppercase tracking-[0.3em] text-green-700 dark:text-green-400 mb-2 font-bold" style={{ fontFamily: "'Manrope', sans-serif" }}>
                        {evento.categoria}
                    </p>
                )}
                <h1 className="text-center font-black text-gray-900 dark:text-white leading-tight" style={{ fontSize: 'clamp(2rem, 5vw, 3.2rem)' }}>
                    {evento.title}
                </h1>
                <div className="flex flex-wrap items-center justify-center gap-2 text-center text-xs mt-3 mb-6 border-b border-gray-300 dark:border-gray-700 pb-6" style={{ fontFamily: "'Manrope', sans-serif" }}>
                    <span className="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-3 py-1 rounded-full"><CalendarDaysIcon className="w-3.5 h-3.5" /> Inicio: {formatearFecha(evento.starts_at)}</span>
                    {evento.ends_at && <span className="flex items-center gap-1 bg-gold-50 dark:bg-gold-500/10 text-gold-700 dark:text-gold-300 px-3 py-1 rounded-full"><FlagIcon className="w-3.5 h-3.5" /> Fin: {formatearFecha(evento.ends_at)}</span>}
                    <span className={`flex items-center gap-1 font-semibold px-3 py-1 rounded-full uppercase tracking-wide text-[10px] ${pasado ? 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'}`}>
                        <span className="w-1.5 h-1.5 rounded-full bg-current" /> {pasado ? 'Evento pasado' : 'Evento actual'}
                    </span>
                </div>

                {/* ===== Galería / carrusel de fotos y videos ===== */}
                {imgs.length > 0 && (
                    <div className="mb-8">
                        <div className="relative h-64 sm:h-80 md:h-96 bg-gray-900 overflow-hidden select-none rounded-lg">
                            {imgs.map((src, idx) => (
                                esVideoUrl(src) ? (
                                    <video key={idx} src={src} controls={idx === imgActiva} muted playsInline preload="metadata"
                                        className={`absolute inset-0 w-full h-full object-contain transition-opacity duration-500 ${idx === imgActiva ? 'opacity-100' : 'opacity-0 pointer-events-none'}`} />
                                ) : (
                                    <img key={idx} src={src} alt={`${evento.title} ${idx + 1}`}
                                        className={`absolute inset-0 w-full h-full object-contain transition-opacity duration-500 ${idx === imgActiva ? 'opacity-100' : 'opacity-0 pointer-events-none'}`} />
                                )
                            ))}
                            {imgs.length > 1 && (
                                <>
                                    <button onClick={prev} aria-label="Anterior"
                                        className="absolute left-3 top-1/2 -translate-y-1/2 z-10 bg-black/50 hover:bg-black/75 text-white rounded-full w-10 h-10 flex items-center justify-center backdrop-blur-sm transition-transform duration-200 ease-out hover:scale-110 active:scale-95">
                                        <ChevronLeftIcon className="w-5 h-5" />
                                    </button>
                                    <button onClick={next} aria-label="Siguiente"
                                        className="absolute right-3 top-1/2 -translate-y-1/2 z-10 bg-black/50 hover:bg-black/75 text-white rounded-full w-10 h-10 flex items-center justify-center backdrop-blur-sm transition-transform duration-200 ease-out hover:scale-110 active:scale-95">
                                        <ChevronRightIcon className="w-5 h-5" />
                                    </button>
                                    <div className="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
                                        {imgs.map((_, idx) => (
                                            <button key={idx} onClick={() => setImgActiva(idx)} aria-label={`Ir al elemento ${idx + 1}`}
                                                className={`rounded-full transition-all duration-300 ${idx === imgActiva ? 'bg-white w-4 h-2' : 'bg-white/50 w-2 h-2 hover:bg-white/80'}`} />
                                        ))}
                                    </div>
                                    <span className="absolute top-3 left-3 z-10 bg-black/50 text-white text-xs px-2 py-0.5 rounded-full backdrop-blur-sm">
                                        {imgActiva + 1} / {imgs.length}
                                    </span>
                                </>
                            )}
                        </div>
                        {imgs.length > 1 && (
                            <div className="flex gap-2 mt-2 overflow-x-auto">
                                {imgs.map((src, idx) => (
                                    <div key={idx} onClick={() => setImgActiva(idx)}
                                        className={`relative h-16 w-24 shrink-0 rounded cursor-pointer border-2 transition-[transform,border-color,opacity] duration-200 ease-out overflow-hidden ${idx === imgActiva ? 'border-green-600 scale-105' : 'border-transparent opacity-60 hover:opacity-100'}`}>
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
                    </div>
                )}

                {/* ===== Descripción completa ===== */}
                <div className="text-justify text-gray-700 dark:text-gray-300 leading-7 whitespace-pre-wrap pb-6">
                    {evento.description || 'Sin descripción disponible.'}
                </div>
            </article>

            {/* ===== Otros eventos ===== */}
            {sugeridos.length > 0 && (
                <section className="border-t-2 border-green-800 dark:border-green-700 pt-6 mt-8">
                    <h2 className="text-center font-black text-gray-900 dark:text-white mb-6" style={{ fontSize: 'clamp(1.3rem, 3vw, 1.8rem)' }}>
                        Otros eventos
                    </h2>
                    <StaggerGrid className="grid sm:grid-cols-3 gap-6" style={{ fontFamily: "'Manrope', sans-serif" }}>
                        {sugeridos.map(ev => {
                            const imgSrc = resolverImagen(ev.image_url);
                            return (
                                <Link to={`/eventos/${ev.id}`} key={ev.id} className="group block">
                                    <div className="relative h-32 rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600 mb-2 bg-gray-100 dark:bg-gray-700">
                                        {imgSrc
                                            ? (esVideoUrl(ev.image_url)
                                                ? <video src={imgSrc} muted playsInline preload="metadata" className="w-full h-full object-cover" />
                                                : <img src={imgSrc} alt={ev.title} loading="lazy" decoding="async" className="w-full h-full object-cover" />)
                                            : <div className="w-full h-full flex items-center justify-center"><PhotoIcon className="w-8 h-8 text-gray-400 dark:text-gray-500" /></div>}
                                    </div>
                                    <h3 className="font-bold text-sm text-gray-900 dark:text-white leading-snug group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors" style={{ fontFamily: SERIF }}>
                                        {ev.title}
                                    </h3>
                                    <span className="inline-flex items-center gap-1 text-xs font-semibold text-green-700 dark:text-green-400 mt-1 group-hover:gap-1.5 transition-[gap] duration-200 ease-out">
                                        Ver detalles <ArrowRightIcon className="w-3 h-3" />
                                    </span>
                                </Link>
                            );
                        })}
                    </StaggerGrid>
                </section>
            )}
        </div>
    );
}
