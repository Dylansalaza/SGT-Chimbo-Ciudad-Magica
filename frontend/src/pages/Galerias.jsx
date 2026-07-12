import { useEffect, useState } from 'react';
import axios from 'axios';
import Reveal from '../components/Reveal';
import { XMarkIcon, PlayIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/24/solid';

// Base del backend Laravel, derivada de VITE_API_URL (quitando el sufijo /api).
// En producción VITE_API_URL apunta al dominio HTTPS real; en local cae al 127.0.0.1.
const LARAVEL_URL = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:3000/api').replace('/api', '');
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

// Las categorías del filtro ya NO son una lista fija: se generan dinámicamente
// a partir de las categorías que realmente traen las galerías (ver `categorias`
// dentro del componente), así nunca se desincronizan con las de eventos/noticias.

// ============================================================================
// COMPONENTE PRINCIPAL: Galerias (ruta /galerias)
// Muestra las galerías de fotos/videos en una grilla simétrica (tarjetas de
// igual tamaño).
// Al hacer clic en una galería se abre un modal con todas sus imágenes, y al
// hacer clic en una imagen se abre un lightbox de pantalla completa navegable
// con teclado (flechas ← → y Escape).
// ============================================================================
export default function Galerias() {
    const [galerias, setGalerias]             = useState([]); // Lista completa traída del backend
    const [cargando, setCargando]             = useState(true);
    const [filtro, setFiltro]                 = useState('Todas'); // Categoría seleccionada
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

    // Trae TODAS las galerías publicadas desde la API pública de Laravel.
    // La API pagina (10 por página), así que recorremos todas las páginas hasta
    // `last_page` y las acumulamos; si algún día la API devolviera un array
    // plano, también se soporta. Las portadas usan loading="lazy", por lo que
    // traer todos los álbumes es liviano (no descarga todas las fotos).
    const cargarGalerias = async () => {
        try {
            let pagina = 1;
            let ultima = 1;
            const acumuladas = [];
            do {
                const { data } = await axios.get(`${LARAVEL_URL}/api/galleries?page=${pagina}`);
                const items = Array.isArray(data) ? data : (data.data || []);
                acumuladas.push(...items);
                ultima = Array.isArray(data) ? 1 : (data.last_page || 1);
                pagina++;
            } while (pagina <= ultima);
            setGalerias(acumuladas);
        } catch (error) {
            console.error('Error cargando galerías:', error);
        } finally {
            setCargando(false);
        }
    };

    // Categorías del filtro: se generan a partir de las que realmente traen las
    // galerías cargadas (deduplicadas y ordenadas), con "Todas" al inicio. Así
    // aparecen solas cuando creas eventos/noticias con categorías nuevas.
    const categorias = ['Todas', ...Array.from(
        new Set(galerias.map(g => g.category).filter(Boolean))
    ).sort((a, b) => a.localeCompare(b, 'es'))];

    // Filtra las galerías por la categoría seleccionada.
    const galeriasFiltradas = filtro === 'Todas'
        ? galerias
        : galerias.filter((g) => g.category === filtro);

    // Nota: NO hacemos early-return al cargar. El masthead y los filtros son
    // estáticos; se renderizan SIEMPRE en la misma posición y solo el mosaico
    // muestra un skeleton mientras carga (evita que el contenido superior
    // aparezca tarde empujando todo hacia abajo → CLS).

    return (
        <div className="max-w-7xl mx-auto px-4 py-8">

            <div className="max-w-5xl mx-auto" style={{ fontFamily: SERIF }}>
                {/* ===== MASTHEAD ===== */}
                <Reveal as="header" className="mb-3">
                    <div className="h-1 w-full bg-gray-900 dark:bg-gray-500 rounded-full mb-1.5" />
                    <div className="border-y-[3px] border-double border-gray-900 dark:border-gray-500 py-2 my-1 text-center">
                        <h1 className="text-gray-900 dark:text-white leading-none" style={{ fontWeight: 900, fontSize: 'clamp(2.5rem, 8vw, 5rem)', letterSpacing: '-0.02em' }}>
                            Galería de Fotos
                        </h1>
                    </div>
                    <div className="flex items-center gap-3 text-center justify-center text-[10px] md:text-xs uppercase tracking-[0.35em] text-gray-600 dark:text-gray-400 my-1" style={{ fontFamily: "'Manrope', sans-serif" }}>
                        <span className="flex-1 border-t border-gray-400 dark:border-white/20" />
                        <span>Recuerdos visuales de San José de Chimbo</span>
                        <span className="flex-1 border-t border-gray-400 dark:border-white/20" />
                    </div>
                </Reveal>

                {/* ── Filtros ── */}
                <div className="flex gap-2 flex-wrap mb-8 items-center text-xs" style={{ fontFamily: "'Manrope', sans-serif" }}>
                    {categorias.map(cat => (
                        <button
                            key={cat}
                            onClick={() => setFiltro(cat)}
                            className={`btn-press px-3 py-1.5 border
                                ${filtro === cat
                                    ? 'bg-green-700 text-white border-green-700 shadow-green-md'
                                    : 'bg-white dark:bg-[#242424] text-gray-700 dark:text-gray-100 border-gray-300 dark:border-gray-600 hover:border-green-500 hover:text-green-700 dark:hover:text-green-400'
                                }`}
                        >
                            {cat}
                        </button>
                    ))}
                </div>
            </div>

            {cargando ? (
                /* Skeleton con el MISMO mosaico (columnas + gaps parejos) que el
                   contenido real, para que no haya salto al llegar. */
                <div className="columns-2 md:columns-3 lg:columns-4 [column-gap:1rem]">
                    {['4/5','1/1','3/4','5/6','3/4','1/1','4/5','5/6'].map((ar, i) => (
                        <div
                            key={i}
                            className="mb-4 break-inside-avoid rounded-2xl overflow-hidden ring-1 ring-black/5 dark:ring-white/10 bg-gray-200 dark:bg-gray-700 animate-pulse"
                            style={{ aspectRatio: ar }}
                        />
                    ))}
                </div>
            ) : galeriasFiltradas.length === 0 ? (
                <div className="flex flex-col items-center justify-center text-center py-16 px-6 border border-dashed border-green-300/60 dark:border-green-800/60 bg-green-50/40 dark:bg-green-900/10 rounded-2xl" style={{ fontFamily: "'Manrope', sans-serif" }}>
                    <div className="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center mb-3">
                        <PlayIcon className="w-6 h-6 text-green-700 dark:text-green-400" />
                    </div>
                    <p className="text-gray-600 dark:text-gray-300 font-medium">
                        {filtro !== 'Todas' ? 'No hay galerías en esta categoría' : 'No hay galerías registradas'}
                    </p>
                    <p className="text-gray-400 text-sm mt-1">
                        {filtro !== 'Todas' ? 'Prueba con otra categoría' : 'Pronto agregaremos contenido'}
                    </p>
                </div>
            ) : (
                <>
                    {/* ── Mosaico: imágenes en su proporción real, con espacio
                        parejo (column-gap 1rem = margen inferior mb-4). ── */}
                    <div className="animate-fade-in-up columns-2 md:columns-3 lg:columns-4 [column-gap:1rem]">
                        {galeriasFiltradas.map((galeria, idx) => {
                            const imgsRaw = galeria.images || [];
                            const imgs    = imgsRaw.map(resolverImagen).filter(Boolean);
                            const portada = imgs[0] || `https://picsum.photos/id/${100 + idx}/500/600`;
                            const portadaEsVideo = imgs[0] && esVideoUrl(imgsRaw[0]);
                            return (
                                <div
                                    key={galeria.id}
                                    onClick={() => { setGaleriaAbierta(galeria); setLbIndex(null); }}
                                    className="group relative mb-4 break-inside-avoid rounded-2xl overflow-hidden cursor-pointer shadow-green-sm hover:shadow-green-lg transition-[box-shadow] duration-300 ease-out ring-1 ring-black/5 dark:ring-white/10 bg-white dark:bg-[#242424]"
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
                                            <span className="w-1.5 h-1.5 rounded-full bg-gold-400"></span> Destacada
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
                        {/* Mosaico en proporción real (columnas), con gaps
                            uniformes de 10px en ambas direcciones (columnGap =
                            margen inferior). Click en una imagen abre el lightbox. */}
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
                                            className="w-full block rounded-lg cursor-pointer hover:opacity-90 transition border border-gray-100 dark:border-gray-700"
                                            style={{ breakInside: 'avoid', marginBottom: '10px' }}
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
                                        className="w-full block rounded-lg cursor-zoom-in hover:opacity-90 transition border border-gray-100 dark:border-gray-700"
                                        style={{ breakInside: 'avoid', marginBottom: '10px' }}
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
                                    className="absolute left-4 top-1/2 -translate-y-1/2 z-20 bg-white/10 hover:bg-white/20 text-white rounded-full w-12 h-12 flex items-center justify-center transition-transform duration-200 ease-out hover:scale-110 active:scale-95">
                                    <ChevronLeftIcon className="w-6 h-6" />
                                </button>
                                <button onClick={next}
                                    className="absolute right-4 top-1/2 -translate-y-1/2 z-20 bg-white/10 hover:bg-white/20 text-white rounded-full w-12 h-12 flex items-center justify-center transition-transform duration-200 ease-out hover:scale-110 active:scale-95">
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