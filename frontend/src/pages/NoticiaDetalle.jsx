import { useEffect, useMemo, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import axios from 'axios';
import StaggerGrid from '../components/StaggerGrid';
import {
  ArrowLeftIcon,
  ArrowRightIcon,
  CalendarDaysIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
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

// Detecta si una URL corresponde a un archivo de video (para renderizar <video>
// en vez de <img>). La portada/galería de una noticia puede ser imagen o video.
function esVideo(url) {
    return /\.(mp4|webm|ogg|mov|m4v)(\?|$)/i.test(url || '');
}

// Convierte cualquier fecha a 'YYYY-MM-DD' en horario LOCAL (no UTC)
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

// Una noticia se considera "actual" solo el día en que fue publicada
function esNoticiaPasada(n) {
    const dia = aDiaLocal(n.published_at);
    const hoy = aDiaLocal(new Date());
    if (!dia || !hoy) return false;
    return dia < hoy;
}

// Arma el array de imágenes de una noticia (portada + galería adicional)
function galeriaDe(noticia) {
    const imgs = [];
    if (noticia.image_url) imgs.push(resolverImagen(noticia.image_url));
    (noticia.images || []).forEach(u => { const r = resolverImagen(u); if (r) imgs.push(r); });
    return imgs;
}

// Mezcla un array (Fisher-Yates) sin mutar el original, para que "Otras
// noticias" muestre una selección distinta cada vez en lugar de siempre
// las primeras del listado.
function mezclar(arr) {
    const copia = [...arr];
    for (let i = copia.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [copia[i], copia[j]] = [copia[j], copia[i]];
    }
    return copia;
}

// ============================================================================
// COMPONENTE: NoticiaDetalle (ruta /noticias/:id)
// Página completa de lectura de una noticia individual (ya no un modal sobre
// la lista). Incluye galería de fotos con carrusel, texto completo y, al
// final, una sección "Otras noticias" para seguir navegando sin volver atrás.
// ============================================================================
export default function NoticiaDetalle() {
    const { id } = useParams();
    const navigate = useNavigate();

    const [noticia, setNoticia]     = useState(null);
    const [otras, setOtras]         = useState([]);
    const [cargando, setCargando]   = useState(true);
    const [error, setError]         = useState(false);
    const [imgActiva, setImgActiva] = useState(0);

    useEffect(() => {
        setCargando(true);
        setError(false);
        setImgActiva(0);
        window.scrollTo({ top: 0, left: 0, behavior: 'instant' });

        axios.get(`${LARAVEL_URL}/api/news/${id}`)
            .then(res => setNoticia(res.data.data || res.data))
            .catch(() => setError(true))
            .finally(() => setCargando(false));

        axios.get(`${LARAVEL_URL}/api/news`)
            .then(res => setOtras(res.data.data || res.data || []))
            .catch(() => {});
    }, [id]);

    // Selección aleatoria de "Otras noticias" (se remezcla solo al cambiar
    // de noticia o al llegar la lista, no en cada render).
    const sugeridas = useMemo(
        () => mezclar(otras.filter(n => n.id !== Number(id))).slice(0, 3),
        [otras, id]
    );

    if (cargando) {
        return (
            <div className="flex justify-center items-center h-64">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-800 dark:border-gray-300 mx-auto" />
                    <p className="mt-4 text-gray-500 dark:text-gray-400">Cargando noticia...</p>
                </div>
            </div>
        );
    }

    if (error || !noticia) {
        return (
            <div className="max-w-2xl mx-auto px-4 py-20 text-center" style={{ fontFamily: SERIF }}>
                <h1 className="text-3xl font-black text-gray-900 dark:text-white mb-3">Noticia no encontrada</h1>
                <p className="text-gray-500 dark:text-gray-400 mb-6">Puede que haya sido retirada o el enlace sea incorrecto.</p>
                <Link to="/noticias" className="inline-flex items-center gap-1.5 px-5 py-2.5 bg-gray-900 dark:bg-gray-700 text-white rounded-full font-semibold text-sm hover:bg-gray-700 dark:hover:bg-gray-600 transition">
                    <ArrowLeftIcon className="w-4 h-4" /> Volver a Noticias
                </Link>
            </div>
        );
    }

    const imgs = galeriaDe(noticia);
    const prev = () => setImgActiva(i => (i - 1 + imgs.length) % imgs.length);
    const next = () => setImgActiva(i => (i + 1) % imgs.length);

    return (
        <div>
        <div className="max-w-7xl mx-auto px-4 py-8" style={{ fontFamily: SERIF }}>

            {/* ===== Volver ===== */}
            <button
                onClick={() => navigate('/noticias')}
                className="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white mb-6 transition-colors"
                style={{ fontFamily: "'Manrope', sans-serif" }}
            >
                <ArrowLeftIcon className="w-4 h-4" /> Volver a Noticias
            </button>

            {/* ===== Encabezado del artículo ===== */}
            <article className="animate-fade-in-up">
                {noticia.categoria && (
                    <p className="text-center text-[11px] uppercase tracking-[0.3em] text-green-700 dark:text-green-400 mb-2 font-bold" style={{ fontFamily: "'Manrope', sans-serif" }}>
                        {noticia.categoria}
                    </p>
                )}
                <h1 className="text-center font-black text-gray-900 dark:text-white leading-tight" style={{ fontSize: 'clamp(2rem, 5vw, 3.2rem)' }}>
                    {noticia.title}
                </h1>
                <p className="flex items-center justify-center gap-2 text-center text-xs text-gray-500 dark:text-gray-400 italic mt-3 mb-6 border-b border-gray-300 dark:border-gray-700 pb-6">
                    <span className="flex items-center gap-1"><CalendarDaysIcon className="w-3.5 h-3.5" /> {formatearFecha(noticia.published_at)}</span>
                    <span className={`flex items-center gap-1 not-italic font-semibold px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wide ${esNoticiaPasada(noticia) ? 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'}`}>
                        <span className="w-1.5 h-1.5 rounded-full bg-current" /> {esNoticiaPasada(noticia) ? 'Noticia pasada' : 'Noticia actual'}
                    </span>
                </p>

                {/* ===== Galería / carrusel de fotos ===== */}
                {imgs.length > 0 && (
                    <div className="mb-8">
                        <div className="relative h-64 sm:h-80 md:h-96 bg-gray-900 overflow-hidden select-none rounded-lg">
                            {imgs.map((src, idx) => (
                                esVideo(src) ? (
                                    <video key={idx} src={src} controls playsInline
                                        className={`absolute inset-0 w-full h-full object-contain bg-black transition-opacity duration-500 ${idx === imgActiva ? 'opacity-100' : 'opacity-0 pointer-events-none'}`} />
                                ) : (
                                    <img key={idx} src={src} alt={`${noticia.title} ${idx + 1}`}
                                        className={`absolute inset-0 w-full h-full object-contain transition-opacity duration-500 ${idx === imgActiva ? 'opacity-100' : 'opacity-0 pointer-events-none'}`} />
                                )
                            ))}
                            {imgs.length > 1 && (
                                <>
                                    <button onClick={prev} aria-label="Foto anterior"
                                        className="absolute left-3 top-1/2 -translate-y-1/2 z-10 bg-black/50 hover:bg-black/75 text-white rounded-full w-10 h-10 flex items-center justify-center backdrop-blur-sm transition-transform duration-200 ease-out hover:scale-110 active:scale-95">
                                        <ChevronLeftIcon className="w-5 h-5" />
                                    </button>
                                    <button onClick={next} aria-label="Foto siguiente"
                                        className="absolute right-3 top-1/2 -translate-y-1/2 z-10 bg-black/50 hover:bg-black/75 text-white rounded-full w-10 h-10 flex items-center justify-center backdrop-blur-sm transition-transform duration-200 ease-out hover:scale-110 active:scale-95">
                                        <ChevronRightIcon className="w-5 h-5" />
                                    </button>
                                    <div className="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
                                        {imgs.map((_, idx) => (
                                            <button key={idx} onClick={() => setImgActiva(idx)} aria-label={`Ir a la foto ${idx + 1}`}
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
                                    esVideo(src) ? (
                                        <div key={idx} onClick={() => setImgActiva(idx)}
                                            className={`relative h-16 w-24 cursor-pointer shrink-0 rounded border-2 overflow-hidden transition-all ${idx === imgActiva ? 'border-gray-900 dark:border-gray-300 scale-105' : 'border-transparent opacity-60 hover:opacity-100'}`}>
                                            <video src={src} muted playsInline className="h-full w-full object-cover bg-black" />
                                            <span className="absolute inset-0 flex items-center justify-center pointer-events-none">
                                                <PlayCircleIcon className="w-6 h-6 text-white/90 drop-shadow" />
                                            </span>
                                        </div>
                                    ) : (
                                        <img key={idx} src={src} onClick={() => setImgActiva(idx)}
                                            className={`h-16 w-24 object-cover cursor-pointer shrink-0 rounded border-2 transition-all ${idx === imgActiva ? 'border-gray-900 dark:border-gray-300 scale-105' : 'border-transparent opacity-60 hover:opacity-100'}`} />
                                    )
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* ===== Cuerpo del artículo ===== */}
                <div className="text-justify text-gray-800 dark:text-gray-300 leading-relaxed text-[17px] whitespace-pre-wrap first-letter:text-6xl first-letter:font-black first-letter:mr-2 first-letter:float-left first-letter:leading-[0.8] pb-6">
                    {noticia.body}
                </div>
            </article>

            {/* ===== Otras noticias ===== */}
            {sugeridas.length > 0 && (
                <section className="border-t-2 border-green-800 dark:border-green-700 pt-6 mt-8">
                    <h2 className="text-center font-black text-gray-900 dark:text-white mb-6" style={{ fontSize: 'clamp(1.3rem, 3vw, 1.8rem)' }}>
                        Otras noticias
                    </h2>
                    <StaggerGrid className="grid sm:grid-cols-3 gap-6" style={{ fontFamily: "'Manrope', sans-serif" }}>
                        {sugeridas.map(n => (
                            <Link to={`/noticias/${n.id}`} key={n.id} className="group block">
                                {resolverImagen(n.image_url) && (
                                    esVideo(n.image_url) ? (
                                        <div className="relative w-full h-32 rounded-lg border border-gray-300 dark:border-gray-600 mb-2 overflow-hidden bg-black">
                                            <video src={resolverImagen(n.image_url)} muted playsInline preload="metadata" className="w-full h-full object-cover" />
                                            <span className="absolute inset-0 flex items-center justify-center pointer-events-none">
                                                <PlayCircleIcon className="w-9 h-9 text-white/90 drop-shadow" />
                                            </span>
                                        </div>
                                    ) : (
                                        <img src={resolverImagen(n.image_url)} alt={n.title} loading="lazy" decoding="async"
                                            className="w-full h-32 object-cover rounded-lg border border-gray-300 dark:border-gray-600 mb-2" />
                                    )
                                )}
                                <h3 className="font-bold text-sm text-gray-900 dark:text-white leading-snug group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" style={{ fontFamily: SERIF }}>
                                    {n.title}
                                </h3>
                                <span className="inline-flex items-center gap-1 text-xs font-semibold text-gray-500 dark:text-gray-400 mt-1 group-hover:gap-1.5 transition-all">
                                    Leer más <ArrowRightIcon className="w-3 h-3" />
                                </span>
                            </Link>
                        ))}
                    </StaggerGrid>
                </section>
            )}
        </div>
        </div>
    );
}
