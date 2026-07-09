import { useEffect, useState } from 'react';
import axios from 'axios';
import { XMarkIcon, PlayIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/24/solid';

const LARAVEL_URL = 'http://127.0.0.1:3000';
const SERIF = "'Playfair Display', Georgia, serif";

// Detecta si una URL es un video (por su extensión), para decidir si se
// renderiza con <video> o con <img> dentro del masonry/modal/lightbox.
const EXT_VIDEO = ['mp4', 'mov', 'webm'];
function esVideoUrl(url) {
    if (!url) return false;
    const ext = url.split('.').pop()?.split('?')[0]?.toLowerCase();
    return EXT_VIDEO.includes(ext);
}

function resolverImagen(url) {
    if (!url) return null;
    // Reapunta cualquier ruta con /storage/ al backend actual (cubre datos viejos).
    const i = url.indexOf('/storage/');
    if (i !== -1) return LARAVEL_URL + url.slice(i);
    if (url.startsWith('http')) return url;          // URL externa
    if (url.startsWith('/'))    return LARAVEL_URL + url;
    return LARAVEL_URL + '/storage/' + url;          // solo el nombre del archivo
}

// Devuelve el día local en formato 'YYYY-MM-DD' (igual que <input type="date">)
function aDiaLocal(fecha) {
    if (!fecha) return null;
    const d = new Date(fecha);
    if (isNaN(d)) return null;
    const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 10);
}

const CATEGORIAS = ['Todas', 'Naturaleza', 'Cultura', 'Gastronomía', 'Fiestas', 'Arquitectura'];

// ============================================================================
// COMPONENTE PRINCIPAL: Galerias (ruta /galerias)
// Muestra las galerías de fotos/videos en un layout tipo "masonry" (columnas).
// Al hacer clic en una galería se abre un modal con todas sus imágenes, y al
// hacer clic en una imagen se abre un lightbox de pantalla completa navegable
// con teclado (flechas ← → y Escape).
// ============================================================================
export default function Galerias() {
    const [galerias, setGalerias]             = useState([]); // Lista completa traída del backend
    const [cargando, setCargando]             = useState(true);
    const [filtro, setFiltro]                 = useState('Todas'); // Categoría seleccionada
    const [fechaFiltro, setFechaFiltro]       = useState(''); // 'YYYY-MM-DD'
    const [galeriaAbierta, setGaleriaAbierta] = useState(null); // Galería abierta en el modal
    const [lbIndex, setLbIndex]               = useState(null); // Índice de la imagen abierta en el lightbox (null = lightbox cerrado)

    useEffect(() => { cargarGalerias(); }, []);

    // Controles de teclado del lightbox: flechas para navegar, Escape para cerrar
    useEffect(() => {
        const handleKey = (e) => {
            if (lbIndex === null || !galeriaAbierta) return;
            const imgs = galeriaAbierta.images || [];
            if (e.key === 'ArrowRight') setLbIndex(i => (i + 1) % imgs.length);
            if (e.key === 'ArrowLeft')  setLbIndex(i => (i - 1 + imgs.length) % imgs.length);
            if (e.key === 'Escape')     setLbIndex(null);
        };
        window.addEventListener('keydown', handleKey);
        return () => window.removeEventListener('keydown', handleKey);
    }, [lbIndex, galeriaAbierta]);

    // Trae todas las galerías publicadas desde la API pública de Laravel
    const cargarGalerias = async () => {
        try {
            const response = await axios.get(`${LARAVEL_URL}/api/galleries`);
            setGalerias(response.data.data || response.data || []);
        } catch (error) {
            console.error('Error cargando galerías:', error);
        } finally {
            setCargando(false);
        }
    };

    // Filtra las galerías por categoría seleccionada y/o por fecha de creación
    const galeriasFiltradas = galerias.filter((g) => {
        const okCategoria = filtro === 'Todas' || g.category === filtro;
        const okFecha     = !fechaFiltro || aDiaLocal(g.created_at) === fechaFiltro;
        return okCategoria && okFecha;
    });

    if (cargando) {
        return (
            <div className="flex justify-center items-center h-64">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto" />
                    <p className="mt-4 text-gray-500">Cargando galerías...</p>
                </div>
            </div>
        );
    }

    // Layout del masonry: alterna tarjetas tall y wide
    const layoutClasses = ['tall', '', '', 'wide', '', '', ''];

    return (
        <div className="max-w-7xl mx-auto px-4 py-8">

            <div className="max-w-5xl mx-auto" style={{ fontFamily: SERIF }}>
                {/* ===== MASTHEAD ===== */}
                <header className="mb-3">
                    <div className="border-y-[3px] border-double border-gray-900 dark:border-gray-500 py-2 my-1 text-center">
                        <h1 className="text-gray-900 dark:text-white leading-none" style={{ fontWeight: 900, fontSize: 'clamp(2.5rem, 8vw, 5rem)', letterSpacing: '-0.02em' }}>
                            Galería de Fotos
                        </h1>
                    </div>
                    <div className="flex items-center gap-3 text-center justify-center text-[10px] md:text-xs uppercase tracking-[0.35em] text-gray-600 dark:text-gray-400 my-1" style={{ fontFamily: "'Manrope', sans-serif" }}>
                        <span className="flex-1 border-t border-gray-400 dark:border-gray-600" />
                        <span>Recuerdos visuales de San José de Chimbo</span>
                        <span className="flex-1 border-t border-gray-400 dark:border-gray-600" />
                    </div>
                </header>

                {/* ── Filtros ── */}
                <div className="flex gap-2 flex-wrap mb-8 items-center text-xs" style={{ fontFamily: "'Manrope', sans-serif" }}>
                    {CATEGORIAS.map(cat => (
                        <button
                            key={cat}
                            onClick={() => setFiltro(cat)}
                            className={`px-3 py-1.5 border transition-all duration-200
                                ${filtro === cat
                                    ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-gray-900 dark:border-white'
                                    : 'bg-white dark:bg-[#242424] text-gray-700 dark:text-gray-100 border-gray-300 dark:border-gray-600 hover:border-gray-400'
                                }`}
                        >
                            {cat}
                        </button>
                    ))}

                    {/* Filtro por fecha / día */}
                    <span className="ml-auto flex items-center gap-2 text-gray-500 dark:text-gray-300">
                        <input
                            type="date"
                            value={fechaFiltro}
                            onChange={(e) => setFechaFiltro(e.target.value)}
                            className="px-2 py-1.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#242424] text-gray-800 dark:text-gray-100"
                        />
                        {fechaFiltro && (
                            <button
                                onClick={() => setFechaFiltro('')}
                                className="flex items-center gap-1 px-3 py-1.5 border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600"
                            >
                                <XMarkIcon className="w-3.5 h-3.5" /> Limpiar
                            </button>
                        )}
                    </span>
                </div>
            </div>

            {galeriasFiltradas.length === 0 ? (
                <div className="text-center py-16 border border-dashed border-gray-200 dark:border-gray-700 rounded-2xl">
                    <p className="text-gray-400 text-lg">
                        {fechaFiltro || filtro !== 'Todas' ? 'No hay galerías con ese filtro' : 'No hay galerías registradas'}
                    </p>
                    <p className="text-gray-300 text-sm mt-1">
                        {fechaFiltro || filtro !== 'Todas' ? 'Prueba con otra fecha o categoría' : 'Pronto agregaremos contenido'}
                    </p>
                </div>
            ) : (
                <>
                    {/* ── Masonry estilo librería de fotos ── */}
                    <div className="columns-2 md:columns-3 lg:columns-4 [column-gap:1rem]">
                        {galeriasFiltradas.map((galeria, idx) => {
                            const imgsRaw = galeria.images || [];
                            const imgs    = imgsRaw.map(resolverImagen).filter(Boolean);
                            const portada = imgs[0] || `https://picsum.photos/id/${100 + idx}/500/600`;
                            const portadaEsVideo = imgs[0] && esVideoUrl(imgsRaw[0]);
                            return (
                                <div
                                    key={galeria.id}
                                    onClick={() => { setGaleriaAbierta(galeria); setLbIndex(null); }}
                                    className="group relative mb-4 break-inside-avoid rounded-2xl overflow-hidden cursor-pointer shadow-sm hover:shadow-2xl transition-all duration-300 border border-gray-100 dark:border-gray-700 bg-white dark:bg-[#242424]"
                                >
                                    {portadaEsVideo ? (
                                        <video
                                            src={portada}
                                            muted
                                            playsInline
                                            preload="metadata"
                                            className="w-full h-auto block group-hover:scale-105 transition-transform duration-500"
                                        />
                                    ) : (
                                        <img
                                            src={portada}
                                            alt={galeria.title}
                                            loading="lazy"
                                            decoding="async"
                                            className="w-full h-auto block group-hover:scale-105 transition-transform duration-500"
                                            onError={e => { e.target.onerror=null; e.target.src=`https://picsum.photos/id/${100+idx}/500/600`; }}
                                        />
                                    )}
                                    {portadaEsVideo && (
                                        <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                                            <div className="w-12 h-12 rounded-full bg-black/50 flex items-center justify-center text-white"><PlayIcon className="w-5 h-5" /></div>
                                        </div>
                                    )}
                                    {idx === 0 && (
                                        <div className="absolute top-3 left-3 flex items-center gap-1.5 bg-black/55 text-white text-xs px-2.5 py-1 rounded-full">
                                            <span className="w-1.5 h-1.5 rounded-full bg-purple-400"></span> Destacada
                                        </div>
                                    )}
                                    <div className="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-black/80 via-black/30 to-transparent">
                                        <p className="text-white font-semibold text-sm truncate">{galeria.title}</p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                </>
            )}

            {/* ── Modal galería (masonry) ── */}
            {galeriaAbierta && lbIndex === null && (
                <div
                    className="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto"
                    style={{ background: 'rgba(0,0,0,.85)' }}
                    onClick={() => setGaleriaAbierta(null)}
                >
                    <div
                        className="relative w-full max-w-3xl bg-white dark:bg-[#242424] rounded-2xl overflow-hidden mt-8 mb-8 border border-gray-100 dark:border-gray-700"
                        onClick={e => e.stopPropagation()}
                    >
                        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                            <div>
                                <h2 className="text-base font-medium text-gray-800 dark:text-white">{galeriaAbierta.title}</h2>
                                <p className="text-xs text-gray-400 mt-0.5">{(galeriaAbierta.images||[]).length} fotografías · Haz clic para ampliar</p>
                            </div>
                            <button
                                onClick={() => setGaleriaAbierta(null)}
                                className="w-8 h-8 rounded-full border border-gray-200 dark:border-gray-600 flex items-center justify-center text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                            ><XMarkIcon className="w-4 h-4" /></button>
                        </div>
                        <div className="p-4" style={{ columns: 2, columnGap: '10px' }}>
                            {(galeriaAbierta.images || []).map((img, idx) => {
                                const src = resolverImagen(img);
                                if (esVideoUrl(img)) {
                                    return (
                                        <video
                                            key={idx}
                                            src={src}
                                            controls
                                            preload="metadata"
                                            className="w-full block rounded-lg mb-2.5 cursor-pointer hover:opacity-90 transition border border-gray-100 dark:border-gray-700"
                                            style={{ breakInside: 'avoid' }}
                                            onClick={e => { e.stopPropagation(); setLbIndex(idx); }}
                                        />
                                    );
                                }
                                return (
                                    <img
                                        key={idx}
                                        src={src}
                                        alt={`${galeriaAbierta.title} ${idx + 1}`}
                                        loading="lazy"
                                        decoding="async"
                                        className="w-full block rounded-lg mb-2.5 cursor-zoom-in hover:opacity-90 transition border border-gray-100 dark:border-gray-700"
                                        style={{ breakInside: 'avoid' }}
                                        onClick={e => { e.stopPropagation(); setLbIndex(idx); }}
                                        onError={e => { e.target.onerror=null; e.target.src='https://picsum.photos/id/30/400/300'; }}
                                    />
                                );
                            })}
                        </div>
                    </div>
                </div>
            )}

            {/* ── Lightbox ── */}
            {galeriaAbierta && lbIndex !== null && (() => {
                const rawImgs = galeriaAbierta.images || [];
                const imgs    = rawImgs.map(resolverImagen);
                const src     = imgs[lbIndex];
                const esVideo = esVideoUrl(rawImgs[lbIndex]);
                const prev = (e) => { e.stopPropagation(); setLbIndex(i => (i - 1 + imgs.length) % imgs.length); };
                const next = (e) => { e.stopPropagation(); setLbIndex(i => (i + 1) % imgs.length); };
                return (
                    <div
                        className="fixed inset-0 z-[60] flex items-center justify-center p-4"
                        style={{ background: 'rgba(0,0,0,.95)' }}
                        onClick={() => setLbIndex(null)}
                    >
                        <button
                            onClick={() => setLbIndex(null)}
                            className="absolute top-4 right-4 z-20 bg-white/10 hover:bg-white/20 text-white rounded-full w-10 h-10 flex items-center justify-center"
                        ><XMarkIcon className="w-5 h-5" /></button>

                        {imgs.length > 1 && (
                            <>
                                <button onClick={prev}
                                    className="absolute left-4 top-1/2 -translate-y-1/2 z-20 bg-white/10 hover:bg-white/20 text-white rounded-full w-12 h-12 flex items-center justify-center transition-all hover:scale-110">
                                    <ChevronLeftIcon className="w-6 h-6" />
                                </button>
                                <button onClick={next}
                                    className="absolute right-4 top-1/2 -translate-y-1/2 z-20 bg-white/10 hover:bg-white/20 text-white rounded-full w-12 h-12 flex items-center justify-center transition-all hover:scale-110">
                                    <ChevronRightIcon className="w-6 h-6" />
                                </button>
                                <span className="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 bg-black/50 text-white text-xs px-3 py-1 rounded-full">
                                    {lbIndex + 1} / {imgs.length}
                                </span>
                            </>
                        )}

                        <div className="max-w-5xl max-h-[90vh] w-full flex items-center justify-center" onClick={e => e.stopPropagation()}>
                            {esVideo ? (
                                <video src={src} controls autoPlay className="max-w-full max-h-[90vh] rounded-lg" />
                            ) : (
                                <img src={src} alt={`${galeriaAbierta.title} ${lbIndex + 1}`} className="max-w-full max-h-[90vh] object-contain rounded-lg" />
                            )}
                        </div>
                    </div>
                );
            })()}
        </div>
    );
}