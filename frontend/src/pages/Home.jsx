import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import StaggerGrid from '../components/StaggerGrid';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, Pagination, Navigation, EffectFade } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';
import 'swiper/css/effect-fade';
import {
  MapIcon,
  MapPinIcon,
  BuildingLibraryIcon,
  SparklesIcon,
  ArrowRightIcon,
  PhotoIcon,
  ClockIcon,
  BanknotesIcon,
  NewspaperIcon,
  CalendarDaysIcon,
  XMarkIcon,
  PhoneIcon,
  EyeIcon,
  PlayCircleIcon,
} from '@heroicons/react/24/solid';

const API_URL     = import.meta.env.VITE_API_URL || 'http://127.0.0.1:3000/api';
const LARAVEL_URL = API_URL.replace(/\/api$/, '');

// Video vertical grabado en Chimbo, mostrado en la sección Visitas del Home
const VIDEO_CHIMBO = '/media/plaza-chimbo.mp4';

// Fotos del collage junto al video
const FOTOS_CHIMBO = [
  { url: '/media/collage/iglesia.webp', alt: 'Iglesia Matriz de San José de Chimbo, vista aérea' },
  { url: '/media/collage/estatua.webp', alt: 'Estatua de San José, patrono de Chimbo' },
  { url: '/media/collage/vista.webp', alt: 'Vista panorámica del centro de San José de Chimbo' },
];

// Normaliza rutas de imagen (igual criterio que el resto de la app)
function resolverImagen(url) {
  if (!url) return null;
  const i = url.indexOf('/storage/');
  if (i !== -1) return LARAVEL_URL + url.slice(i);
  if (url.startsWith('http')) return url;
  if (url.startsWith('/'))    return LARAVEL_URL + url;
  return LARAVEL_URL + '/storage/' + url;
}

// Detecta si una URL corresponde a un video (portada de noticia en video).
function esVideo(url) {
  return /\.(mp4|webm|ogg|mov|m4v)(\?|$)/i.test(url || '');
}

// El carrusel principal se administra 100% desde el panel (Editar Home).
// Ya no hay imágenes por defecto: si el backend aún no devuelve diapositivas,
// simplemente no se muestra el carrusel (ver la condición carousel.length > 0).
const CARRUSEL_DEFAULT = [];

// Clave para cachear la respuesta de /home en localStorage. Así, al volver al
// Inicio (o en recargas), el carrusel y el contenido se pintan AL INSTANTE
// desde caché y luego se revalidan en segundo plano (stale-while-revalidate),
// en vez de esperar toda la petición de red cada vez. Sube el sufijo (_v2, …)
// si cambia la forma de los datos para invalidar cachés viejas.
const HOME_CACHE_KEY = 'home_cache_v1';

// ============================================================================
// COMPONENTE: Reveal
// Hace aparecer su contenido con un fade + leve desplazamiento cuando entra
// al viewport (IntersectionObserver, solo opacity/transform). `delay` permite
// escalonar varias columnas. Respeta prefers-reduced-motion.
// ============================================================================
function Reveal({ children, delay = 0, className = '' }) {
  const ref = React.useRef(null);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    // En el Home queremos que la animación se REPITA cada vez que la sección
    // entra al viewport: en lugar de desconectar tras el primer disparo,
    // seguimos el estado de intersección (visible al entrar, oculto al salir),
    // así al volver a bajar/subir el efecto de cascada se reproduce de nuevo.
    const obs = new IntersectionObserver(
      ([e]) => setVisible(e.isIntersecting),
      { threshold: 0, rootMargin: '0px 0px -12% 0px' }
    );
    obs.observe(el);
    return () => obs.disconnect();
  }, []);

  return (
    <div
      ref={ref}
      style={{ transitionDelay: `${delay}ms` }}
      // La transición SOLO se aplica cuando el elemento está visible: así la
      // ENTRADA se anima (fade + subida), pero al salir de vista el reseteo a
      // oculto es instantáneo. Como el reseteo ocurre ya fuera de pantalla, el
      // usuario nunca ve un desvanecido: solo la cascada de entrada, cada vez.
      className={`will-change-transform ${
        visible
          ? 'transition-[opacity,transform] duration-700 ease-out opacity-100 translate-y-0'
          : 'opacity-0 translate-y-8'
      } ${className}`}
    >
      {children}
    </div>
  );
}

// ============================================================================
// COMPONENTE: CountUp
// Cuenta de 0 al valor final la primera vez que el número entra al viewport
// (1.2 s, easing suave). Con prefers-reduced-motion muestra el valor directo.
// ============================================================================
function CountUp({ value, duration = 1200 }) {
  const ref = React.useRef(null);
  const [n, setN] = useState(0);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      setN(value);
      return;
    }
    let raf;
    const obs = new IntersectionObserver(([e]) => {
      if (!e.isIntersecting) return;
      obs.disconnect();
      const t0 = performance.now();
      const tick = (t) => {
        const p = Math.min((t - t0) / duration, 1);
        const eased = 1 - Math.pow(1 - p, 3); // ease-out cúbico
        setN(Math.round(value * eased));
        if (p < 1) raf = requestAnimationFrame(tick);
      };
      raf = requestAnimationFrame(tick);
    }, { threshold: 0.4 });
    obs.observe(el);
    return () => { obs.disconnect(); if (raf) cancelAnimationFrame(raf); };
  }, [value, duration]);

  return <span ref={ref} className="tabular-nums">{Number(n).toLocaleString('es-ES')}</span>;
}

// ============================================================================
// COMPONENTE: VideoDeferido
// Reproduce un video en autoplay silencioso, pero SOLO empieza a descargarlo
// cuando está por entrar al viewport (IntersectionObserver). Evita bajar el
// MP4 (~2 MB) en la carga inicial del Home, que penalizaba el Speed Index.
// ============================================================================
function VideoDeferido({ src, className }) {
  const ref = React.useRef(null);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const obs = new IntersectionObserver(
      ([e]) => { if (e.isIntersecting) { setVisible(true); obs.disconnect(); } },
      { rootMargin: '200px' } // empieza a cargar un poco antes de que se vea
    );
    obs.observe(el);
    return () => obs.disconnect();
  }, []);

  // Al hacerse visible, aseguramos la reproducción (algunos navegadores no
  // arrancan el autoplay al asignar el src de forma diferida).
  useEffect(() => {
    if (visible && ref.current) ref.current.play().catch(() => {});
  }, [visible]);

  return (
    <video
      ref={ref}
      src={visible ? src : undefined}
      autoPlay
      muted
      loop
      playsInline
      preload="none"
      className={className}
    />
  );
}

// ============================================================================
// COMPONENTE: LazyImg
// Imagen que SOLO pide su `src` cuando está por entrar al viewport (Intersection
// Observer, margen pequeño). El atributo nativo `loading="lazy"` no basta aquí:
// en conexiones rápidas el navegador precarga imágenes hasta ~3000 px por debajo
// del pliegue, así que las tarjetas de Destacados/Noticias/Eventos (con fotos
// que pueden pesar varios MB) se descargaban durante la carga inicial y le
// robaban ancho de banda al hero (elemento LCP). Con este gate, esas imágenes
// esperan a que el usuario baje, y el hero carga sin competencia.
// El contenedor de cada tarjeta tiene alto fijo, así que no hay salto (CLS).
// ============================================================================
function LazyImg({ src, alt, className }) {
  const ref = React.useRef(null);
  const [show, setShow] = useState(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const obs = new IntersectionObserver(
      ([e]) => { if (e.isIntersecting) { setShow(true); obs.disconnect(); } },
      { rootMargin: '300px' }
    );
    obs.observe(el);
    return () => obs.disconnect();
  }, []);

  return (
    <img
      ref={ref}
      src={show ? src : undefined}
      alt={alt}
      loading="lazy"
      decoding="async"
      className={className}
    />
  );
}

// ============================================================================
// COMPONENTE: AdminAccessCard
// Reemplaza el antiguo formulario de usuario/clave embebido en el Home: el
// acceso administrativo vive en el panel de Laravel (con su propio login),
// así que aquí solo mostramos un botón directo hacia allá. Si ya hay sesión
// de administrador iniciada, se indica en lugar del botón.
// ============================================================================
function AdminAccessCard() {
  const userRaw = localStorage.getItem('user') || sessionStorage.getItem('user');
  const usuario = userRaw ? JSON.parse(userRaw) : null;

  return (
    <div className="bg-white dark:bg-[#242424] rounded-2xl shadow-sm border border-black/5 dark:border-white/10 p-5 text-center hover:shadow-md transition-shadow duration-300">
      <div className="w-11 h-11 mx-auto mb-3 rounded-full bg-green-50 dark:bg-green-900/30 flex items-center justify-center">
        <BuildingLibraryIcon className="w-5 h-5 text-green-700 dark:text-green-400" />
      </div>

      {usuario ? (
        <>
          <h3 className="font-bold text-base text-gray-800 dark:text-white mb-1">Sesión iniciada</h3>
          <p className="text-gray-600 dark:text-gray-300 text-xs leading-relaxed mb-4">
            Bienvenido, <span className="font-semibold">{usuario.name || 'Usuario'}</span>.
          </p>
        </>
      ) : (
        <>
          <h3 className="font-bold text-base text-gray-800 dark:text-white mb-1">Panel de Administración</h3>
          <p className="text-gray-600 dark:text-gray-300 text-xs leading-relaxed mb-4">
            Gestiona lugares, noticias y eventos desde el panel administrativo.
          </p>
        </>
      )}

      <a
        href={`${LARAVEL_URL}/admin`}
        className="btn-press group inline-flex w-full items-center justify-center gap-1.5 px-4 py-2.5 bg-green-700 hover:bg-green-800 text-white font-semibold rounded-lg text-sm shadow-green-md"
      >
        Dirigirse al Panel de Administrador <ArrowRightIcon className="w-4 h-4 transition-transform duration-200 ease-out group-hover:translate-x-0.5" />
      </a>
    </div>
  );
}

// ============================================================================
// COMPONENTE PRINCIPAL: Home
// Página de inicio pública. Muestra un carrusel de imágenes, la sección de
// bienvenida (con collage de fotos), un banner de la Iglesia Matriz, y tres
// secciones de contenido dinámico traídas desde el backend: Lugares
// Destacados, Noticias recientes y Próximos Eventos. Todo el contenido
// (textos, carrusel, secciones visibles) se controla desde el panel admin.
// ============================================================================
export default function Home() {
  // ── Estado del contenido editable desde el panel admin ("Editar Home") ──
  const [welcomeTitle, setWelcomeTitle] = useState('San José de Chimbo'); // Título de la sección Bienvenida
  const [welcomeText, setWelcomeText]   = useState('');                  // Texto descriptivo de Bienvenida (opcional)
  const [carousel, setCarousel]         = useState(CARRUSEL_DEFAULT);    // Slides del carrusel principal
  const [cargandoHome, setCargandoHome] = useState(true);               // true mientras se pide /home (para el placeholder del carrusel)
  const [destacados, setDestacados]     = useState([]);                 // Lugares marcados como "destacado"
  const [noticias, setNoticias]         = useState([]);                 // Últimas noticias publicadas
  const [eventos, setEventos]           = useState([]);                 // Próximos eventos programados
  // Interruptores para mostrar/ocultar cada sección (configurables desde el admin)
  const [secciones, setSecciones]       = useState({ destacados: true, noticias: true, eventos: true });
  const [selectedPlace, setSelectedPlace] = useState(null); // Lugar destacado abierto en el modal de detalle
  const [visitas, setVisitas]           = useState(null);   // Total histórico de visitas (endpoint público /stats)
  // El carrusel usa efecto "fade": Swiper apila TODAS las diapositivas en el
  // viewport (posición absoluta), así que `loading="lazy"` no difiere nada y el
  // navegador descargaba las 5 imágenes a la vez (~1.3 MB). La primera (elemento
  // LCP) competía con ~1 MB de imágenes aún invisibles → el hero tardaba ~10 s
  // en redes lentas. Con este flag, en el primer pintado SOLO la 1ª diapositiva
  // trae `src`; el resto se cargan una vez que el hero (LCP) YA se descargó, así
  // no le roban ancho de banda. El disparo es el `onLoad` de la 1ª imagen; hay
  // un temporizador de respaldo por si esa carga viene de caché o el carrusel
  // está vacío.
  const [slidesListas, setSlidesListas] = useState(false);
  useEffect(() => {
    // Respaldo: si el onLoad de la 1ª diapositiva no llega (caché, sin carrusel),
    // liberamos el resto poco después de que la ventana termine de cargar.
    let timeoutId;
    const liberar = () => { timeoutId = setTimeout(() => setSlidesListas(true), 1200); };
    if (document.readyState === 'complete') liberar();
    else window.addEventListener('load', liberar, { once: true });
    return () => {
      window.removeEventListener('load', liberar);
      if (timeoutId != null) clearTimeout(timeoutId);
    };
  }, []);

  // Formatea una fecha ISO a texto legible en español, ej: "6 de julio de 2026"
  const fmtFecha = (f) => f ? new Date(f).toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' }) : '';

  // Convierte cualquier fecha a 'YYYY-MM-DD' en horario LOCAL (no UTC), para
  // poder comparar solo el día calendario sin que la zona horaria desplace la fecha.
  const aDiaLocal = (fecha) => {
    if (!fecha) return null;
    const d = new Date(fecha);
    if (isNaN(d)) return null;
    const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 10);
  };
  // Evento: pasa a "pasado" en cuanto termina (o empieza, si no tiene fin).
  // Se recalcula en cada render comparando contra la hora actual, así el
  // badge "🟢 Actual / ⚪ Pasado" cambia solo, sin tarea manual ni cron job.
  const esEventoPasado = (ev) => {
    const ref = ev.ends_at || ev.starts_at;
    if (!ref) return false;
    const t = new Date(ref).getTime();
    return !isNaN(t) && t < Date.now();
  };
  // Noticia: "actual" solo el día que se publicó; al día siguiente pasa a "pasada".
  const esNoticiaPasada = (n) => {
    const dia = aDiaLocal(n.published_at);
    const hoy = aDiaLocal(new Date());
    if (!dia || !hoy) return false;
    return dia < hoy;
  };

  // Vuelca la respuesta de /home al estado. Cada campo solo se sobrescribe si el
  // backend efectivamente lo envió, así se conservan los valores por defecto
  // cuando el admin no configuró algo. Se usa tanto para la caché como para la red.
  const aplicarHome = (data) => {
    if (!data) return;
    if (data.welcome_title) setWelcomeTitle(data.welcome_title);
    if (data.welcome_text)  setWelcomeText(data.welcome_text);
    if (Array.isArray(data.carousel) && data.carousel.length) {
      setCarousel(data.carousel.map(s => ({ ...s, url: resolverImagen(s.url) })));
    }
    if (Array.isArray(data.destacados)) setDestacados(data.destacados);
    if (Array.isArray(data.noticias))  setNoticias(data.noticias);
    if (Array.isArray(data.eventos))   setEventos(data.eventos);
    if (data.secciones) setSecciones({ destacados: true, noticias: true, eventos: true, ...data.secciones });
  };

  // Carga inicial: trae todo el contenido dinámico del Home desde un único
  // endpoint (/home). La visita anónima se registra UNA sola vez por sesión
  // en App.jsx (antes también se registraba aquí, lo que inflaba el conteo al
  // dispararse en cada montaje/recarga del Home).
  useEffect(() => {
    // 1) Pintado INSTANTÁNEO desde caché (visitas repetidas / recargas): el
    //    carrusel y el contenido aparecen sin esperar la red. Si no hay caché,
    //    se mantiene el placeholder hasta que responda /home.
    try {
      const cache = localStorage.getItem(HOME_CACHE_KEY);
      if (cache) {
        aplicarHome(JSON.parse(cache));
        setCargandoHome(false);
      }
    } catch { /* caché corrupta: se ignora y se pedirá a la red */ }

    // 2) Revalidación en segundo plano: siempre pedimos la versión fresca y,
    //    si llega, actualizamos estado + caché (stale-while-revalidate).
    //    Reutilizamos la promesa que index.html ya lanzó durante el parseo del
    //    HTML (window.__homePromise): así /home viaja en paralelo con el bundle
    //    de React y la imagen del carrusel (LCP) se descubre antes. Si no existe
    //    (build sin VITE_API_URL, o segundo montaje), pedimos normalmente.
    const cargar = async () => {
      try {
        const early = typeof window !== 'undefined' ? window.__homePromise : null;
        if (early) { window.__homePromise = null; } // usar una sola vez
        const data = early ? await early : await (await fetch(`${API_URL}/home`)).json();
        if (!data) throw new Error('home vacío');
        aplicarHome(data);
        try { localStorage.setItem(HOME_CACHE_KEY, JSON.stringify(data)); } catch { /* almacenamiento lleno: no bloquea */ }
      } catch (err) {
        console.error('Error cargando el Home:', err);
      } finally {
        // Pase lo que pase, dejamos de mostrar el placeholder del carrusel.
        setCargandoHome(false);
      }
    };
    cargar();

    // Contador público de visitas (si falla, el bloque no se muestra)
    fetch(`${API_URL}/stats`)
      .then((r) => r.json())
      .then((d) => setVisitas(d?.totales?.historico ?? null))
      .catch(() => {});
  }, []);

  // Abre el modal de detalle de un lugar destacado y bloquea el scroll del fondo
  const openModal = (place) => {
    setSelectedPlace(place);
    document.body.style.overflow = 'hidden';
  };
  // Cierra el modal y restaura el scroll normal de la página
  const closeModal = () => {
    setSelectedPlace(null);
    document.body.style.overflow = 'auto';
  };

  // Tarjeta de un lugar destacado. Se usa tanto en la grilla (≤3 lugares) como
  // en el carrusel (>3 lugares), para no duplicar el markup.
  const TarjetaLugar = (lugar) => {
    const img = resolverImagen(lugar.imagen_url);
    return (
      <div key={lugar.id} className="group relative h-full bg-white dark:bg-[#242424] rounded-2xl shadow-green-sm ring-1 ring-black/5 dark:ring-white/10 overflow-hidden hover:shadow-green-lg hover:-translate-y-1 transition-[transform,box-shadow] duration-300 ease-out cursor-pointer" onClick={() => openModal(lugar)}>
        <div className="relative h-56 overflow-hidden bg-gray-200 dark:bg-gray-700">
          {img ? (
            <LazyImg src={img} alt={lugar.nombre} className="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105" />
          ) : (
            <div className="w-full h-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"><PhotoIcon className="w-12 h-12 text-gray-400 dark:text-gray-500" /></div>
          )}
          <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
          {lugar.categoria && (
            <span className="absolute top-3 left-3 bg-green-700/95 backdrop-blur-sm text-white text-xs font-medium px-3 py-1 rounded-full ring-1 ring-inset ring-white/20 shadow-lg">
              {lugar.categoria}
            </span>
          )}
        </div>
        <div className="p-4">
          <h3 className="font-bold text-lg text-gray-800 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors">{lugar.nombre}</h3>
          <div className="flex justify-between items-center mt-2 text-sm text-gray-500 dark:text-gray-400">
            {lugar.horario && <span className="flex items-center gap-1"><ClockIcon className="w-3.5 h-3.5" /> {lugar.horario}</span>}
            {lugar.precio && <span className="flex items-center gap-1"><BanknotesIcon className="w-3.5 h-3.5" /> {lugar.precio}</span>}
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-gray-50 to-white dark:from-[#242424] dark:to-gray-800">

      {/* Carrusel principal. Mientras se pide /home mostramos un placeholder
          del MISMO alto, para que la página no "salte" cuando lleguen las
          imágenes. Ya cargado: si hay diapositivas se muestra el carrusel;
          si el admin no configuró ninguna, no se muestra nada. */}
      {(
        // Contenedor de ALTO FIJO que SIEMPRE se renderiza (nunca colapsa), pase
        // lo que pase con la carga. Así la sección de Bienvenida de abajo NUNCA
        // se desplaza (evita CLS), aunque el carrusel tarde, esté vacío o la API
        // falle. Casos: cargando → placeholder; con slides → carrusel; sin slides
        // → imagen estática de respaldo (nunca un hueco que colapse).
        <div className="relative bg-black h-[420px] sm:h-[480px] md:h-[560px] lg:h-[640px] w-full overflow-hidden">
          {cargandoHome ? (
            // Placeholder de marca (verde + barrido dorado). Estilos en index.css → .hero-skeleton.
            <div className="hero-skeleton absolute inset-0" />
          ) : carousel.length > 0 ? (
            <Swiper
              modules={[Autoplay, Pagination, EffectFade]}
              effect="fade"
              autoplay={{ delay: 4000, disableOnInteraction: false }}
              pagination={{ clickable: true, dynamicBullets: true }}
              loop={carousel.length > 1}
              className="h-full w-full"
            >
              {carousel.map((image, idx) => (
                <SwiperSlide key={idx}>
                  <div className="relative h-full w-full">
                    {/* Solo la 1ª diapositiva (elemento LCP) se descarga en el
                        primer pintado, con prioridad alta. En cuanto ESA termina
                        (onLoad), se libera el resto (`slidesListas`), así no le
                        roban ancho de banda al hero. Se pintan antes del primer
                        avance del autoplay (4 s). */}
                    <img
                      src={(idx === 0 || slidesListas) ? image.url : undefined}
                      alt={image.title || ''}
                      className="w-full h-full object-cover"
                      loading={idx === 0 ? 'eager' : 'lazy'}
                      fetchpriority={idx === 0 ? 'high' : 'auto'}
                      decoding="async"
                      onLoad={idx === 0 ? () => setSlidesListas(true) : undefined}
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
                    <div className="absolute bottom-0 left-0 right-0 p-8 md:p-12 text-center text-white">
                      <div className="max-w-4xl mx-auto">
                        {image.title && <h2 className="font-serif text-4xl md:text-6xl font-bold mb-4">{image.title}</h2>}
                        {image.subtitle && <p className="text-lg md:text-2xl opacity-90">{image.subtitle}</p>}
                        <Link to="/mapa" className="btn-press inline-flex items-center gap-2 mt-6 px-8 py-3 bg-green-600 hover:bg-green-500 rounded-full text-white font-semibold shadow-green-lg ring-1 ring-inset ring-white/15">
                          <MapIcon className="w-5 h-5" /> Explorar ahora
                        </Link>
                      </div>
                    </div>
                  </div>
                </SwiperSlide>
              ))}
            </Swiper>
          ) : (
            // Sin diapositivas configuradas (o la API no respondió): imagen
            // estática de respaldo para no dejar un hueco negro ni colapsar.
            <img
              src="/media/welcome/chimbo.webp"
              alt="San José de Chimbo"
              className="absolute inset-0 w-full h-full object-cover"
              decoding="async"
            />
          )}
        </div>
      )}

      {/* Bienvenida */}
      <div className="relative py-16 md:py-24 overflow-hidden border-t border-black/5 dark:border-white/10">
        <div className="relative max-w-7xl mx-auto px-4">
          <div className="grid md:grid-cols-2 gap-10 lg:gap-14 items-center">

            {/* ── Columna izquierda: texto ── */}
            <div className="text-left animate-fade-in-up">
              <p className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.3em] text-green-700 dark:text-green-400 mb-3">
                <span className="w-8 h-0.5 rounded-full bg-gold-400" /> Bienvenido a
              </p>
              <h1 className="font-serif text-4xl md:text-5xl font-bold mb-6 text-black dark:text-white">
                <span className="gold-underline">{welcomeTitle}</span>
              </h1>

              {welcomeText && (
                <p className="text-gray-600 dark:text-gray-300 text-lg leading-relaxed mb-4">
                  {welcomeText}
                </p>
              )}

              <p className="text-gray-600 dark:text-gray-300 text-base leading-relaxed mb-6">
                Fundada por Sebastián de Benalcázar en 1535 y elevada a cantón el 3 de marzo de 1860,
                Chimbo es la puerta de entrada a la provincia de Bolívar: un mosaico de páramos, ríos
                y tradiciones artesanales transmitidas de generación en generación. Sus calles con historia,
                su gente hospitalaria y su gastronomía típica hacen de este rincón andino un destino que
                combina historia viva con naturaleza exuberante, ideal para quienes buscan turismo cultural,
                religioso y de aventura en un mismo lugar.
              </p>
            </div>

            {/* ── Columna derecha: collage de fotos ──
                Imágenes AUTO-HOSPEDADAS y optimizadas a WebP (antes venían de
                Wikimedia: externas, pesadas y descubiertas tarde → penalizaban
                el LCP). La principal se carga eager + fetchpriority alta y se
                precarga en index.html, porque suele ser el elemento LCP. */}
            <Reveal delay={120} className="grid grid-cols-2 grid-rows-2 gap-3 h-[320px] sm:h-[380px] md:h-[440px]">
              <img
                src="/media/welcome/chimbo.webp"
                alt="San José de Chimbo"
                loading="eager"
                fetchpriority="high"
                decoding="async"
                className="row-span-2 h-full w-full object-cover rounded-2xl shadow-xl"
              />
              <img
                src="/media/welcome/parque.webp"
                alt="Parque Central de San José de Chimbo"
                loading="eager"
                decoding="async"
                className="h-full w-full object-cover rounded-2xl shadow-xl"
              />
              <img
                src="/media/welcome/torreon.webp"
                alt="El Torreón, San José de Chimbo"
                loading="lazy"
                decoding="async"
                className="h-full w-full object-cover rounded-2xl shadow-xl"
              />
            </Reveal>

          </div>
        </div>
      </div>

      {/* ===== ÍCONO DISTINTIVO: IGLESIA MATRIZ DE SAN JOSÉ DE CHIMBO ===== */}
      <div className="relative py-16 bg-gray-50 dark:bg-gray-800 border-t border-black/5 dark:border-white/10 transition-colors">
        <div className="max-w-7xl mx-auto px-4">
          <Reveal className="relative h-[320px] md:h-[400px] rounded-3xl overflow-hidden shadow-xl">
            <img
              src="/media/collage/iglesia.webp"
              alt="Iglesia Matriz de San José de Chimbo"
              loading="lazy"
              decoding="async"
              className="w-full h-full object-cover object-center"
              onError={(e) => {
                e.target.onerror = null;
                e.target.src = '/media/collage/vista.webp';
              }}
            />
            {/* Gradiente oscuro en la parte inferior */}
            <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent" />
            {/* Texto sobre la imagen */}
            <div className="absolute bottom-0 left-0 right-0 p-8 md:p-12">
              <div className="max-w-3xl">
                <div className="flex items-center gap-3 mb-3">
                  <BuildingLibraryIcon className="text-gold-400 w-7 h-7" />
                  <span className="text-xs font-bold uppercase tracking-[0.25em] text-gold-300">Patrimonio histórico</span>
                </div>
                <h2 className="font-serif text-3xl md:text-5xl font-black text-white leading-tight mb-3">
                  Iglesia Matriz de<br/>San José de Chimbo
                </h2>
                <p className="text-gray-200 text-base md:text-lg max-w-xl leading-relaxed">
                  Erigida en el corazón del cantón, la Iglesia Matriz es el símbolo arquitectónico
                  y espiritual de Chimbo. Su fachada colonial y sus torres gemelas son testigos
                  de siglos de historia, fe y tradición chimbeña.
                </p>
                <div className="flex flex-wrap gap-3 mt-5">
                  <span className="flex items-center gap-1.5 bg-white/20 backdrop-blur-sm text-white text-sm px-4 py-1.5 rounded-full border border-white/30"><BuildingLibraryIcon className="w-4 h-4" /> Arquitectura colonial</span>
                  <span className="flex items-center gap-1.5 bg-white/20 backdrop-blur-sm text-white text-sm px-4 py-1.5 rounded-full border border-white/30"><MapPinIcon className="w-4 h-4" /> Centro histórico</span>
                  <span className="flex items-center gap-1.5 bg-white/20 backdrop-blur-sm text-white text-sm px-4 py-1.5 rounded-full border border-white/30"><SparklesIcon className="w-4 h-4" /> Patrimonio cultural</span>
                </div>
              </div>
            </div>
          </Reveal>
        </div>
      </div>

      {/* ===== SECCIÓN 1 · LUGARES DESTACADOS (banda blanca, limpia) ===== */}
      {secciones.destacados && (
      <div className="relative py-16 bg-white dark:bg-[#242424] border-t border-black/5 dark:border-white/10 transition-colors">
        <div className="relative max-w-7xl mx-auto px-4">
          <Reveal as="div" className="flex flex-col sm:flex-row sm:justify-between sm:items-end mb-10 gap-3">
            <div>
              <p className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-green-700 dark:text-green-400 mb-2">
                <span className="w-8 h-0.5 rounded-full brand-gradient-bar" /> Qué visitar
              </p>
              <h2 className="font-serif text-3xl md:text-4xl font-extrabold text-gray-800 dark:text-white">
                Lugares Destacados
              </h2>
            </div>
            <Link to="/mapa" className="text-green-700 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 text-sm font-semibold flex items-center gap-1 group">
              Ver todos en el mapa <ArrowRightIcon className="w-4 h-4 group-hover:translate-x-1 transition" />
            </Link>
          </Reveal>

          {destacados.length === 0 ? (
            <div className="flex flex-col items-center justify-center text-center py-14 px-6 rounded-2xl border border-dashed border-green-300/60 dark:border-green-800/60 bg-green-50/40 dark:bg-green-900/10">
              <div className="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center mb-3">
                <MapPinIcon className="w-6 h-6 text-green-700 dark:text-green-400" />
              </div>
              <p className="text-gray-600 dark:text-gray-300 font-medium">Aún no hay lugares destacados</p>
              <p className="text-sm text-gray-400 mt-1">Se mostrarán aquí cuando el municipio los publique.</p>
            </div>
          ) : destacados.length > 3 ? (
            // Más de 3 lugares: carrusel horizontal (uno se desliza tras otro)
            // en vez de apilar filas hacia abajo. Muestra 1/2/3 tarjetas según
            // el ancho de pantalla.
            <Swiper
              modules={[Autoplay, Pagination, Navigation]}
              spaceBetween={24}
              slidesPerView={1}
              breakpoints={{ 640: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }}
              pagination={{ clickable: true, dynamicBullets: true }}
              navigation={true}
              autoplay={{ delay: 4500, disableOnInteraction: false }}
              loop={true}
              className="destacados-swiper !px-1 !pb-14 !pt-2"
            >
              {destacados.map((lugar) => (
                <SwiperSlide key={lugar.id} className="!h-auto">
                  {TarjetaLugar(lugar)}
                </SwiperSlide>
              ))}
            </Swiper>
          ) : (
            <StaggerGrid repeat className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {destacados.map(TarjetaLugar)}
            </StaggerGrid>
          )}
        </div>
      </div>
      )}

      {/* ===== SECCIÓN 2 · NOTICIAS (banda oscura, estilo prensa) ===== */}
      {secciones.noticias && (
        <div className="relative py-16 overflow-hidden bg-gray-50 dark:bg-gray-800 border-t border-black/5 dark:border-white/10 transition-colors">
          <div className="relative max-w-7xl mx-auto px-4">
            <Reveal as="div" className="flex flex-col sm:flex-row sm:justify-between sm:items-end mb-10 gap-3">
              <div>
                <p className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-green-700 dark:text-green-400 mb-2">
                  <span className="w-8 h-0.5 rounded-full brand-gradient-bar" /> Actualidad
                </p>
                <h2 className="font-serif text-3xl md:text-4xl font-extrabold text-gray-800 dark:text-white">Noticias recientes</h2>
              </div>
              <Link to="/noticias" className="text-green-700 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 text-sm font-semibold flex items-center gap-1 group">
                Ver todas <ArrowRightIcon className="w-4 h-4 group-hover:translate-x-1 transition" />
              </Link>
            </Reveal>

            {noticias.length === 0 ? (
              <div className="flex flex-col items-center justify-center text-center py-14 px-6 rounded-2xl border border-dashed border-green-300/60 dark:border-green-800/60 bg-green-50/40 dark:bg-green-900/10">
                <div className="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center mb-3">
                  <NewspaperIcon className="w-6 h-6 text-green-700 dark:text-green-400" />
                </div>
                <p className="text-gray-600 dark:text-gray-300 font-medium">Aún no hay noticias publicadas</p>
                <p className="text-sm text-gray-400 mt-1">Vuelve pronto para ver las novedades de Chimbo.</p>
              </div>
            ) : (
            <StaggerGrid repeat className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {noticias.map((n) => {
                const img = resolverImagen(n.image_url);
                return (
                  <Link to={`/noticias/${n.id}`} key={n.id} className="group bg-white dark:bg-[#242424] rounded-2xl shadow-green-sm ring-1 ring-black/5 dark:ring-white/10 overflow-hidden hover:shadow-green-lg hover:-translate-y-1 transition-[transform,box-shadow] duration-300 ease-out">
                    <div className="relative h-44 overflow-hidden bg-gray-200 dark:bg-gray-700">
                      {img ? (
                        esVideo(n.image_url) ? (
                          <>
                            <video src={img} muted playsInline preload="metadata" className="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105" />
                            <span className="absolute inset-0 flex items-center justify-center pointer-events-none"><PlayCircleIcon className="w-12 h-12 text-white/90 drop-shadow" /></span>
                          </>
                        ) : (
                          <LazyImg src={img} alt={n.title} className="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105" />
                        )
                      ) : <div className="w-full h-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"><NewspaperIcon className="w-10 h-10 text-gray-400 dark:text-gray-500" /></div>}
                      {n.categoria && <span className="absolute top-3 left-3 bg-green-700/95 backdrop-blur-sm text-white text-xs font-medium px-3 py-1 rounded-full ring-1 ring-inset ring-white/20">{n.categoria}</span>}
                      <span className={`absolute top-3 right-3 flex items-center gap-1 text-white text-xs px-3 py-1 rounded-full font-semibold ${esNoticiaPasada(n) ? 'bg-gray-600/80' : 'bg-emerald-500/90'}`}>
                        <span className="w-1.5 h-1.5 rounded-full bg-current" /> {esNoticiaPasada(n) ? 'Pasada' : 'Actual'}
                      </span>
                    </div>
                    <div className="p-5">
                      <h3 className="font-bold text-lg text-gray-800 dark:text-white line-clamp-2 group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors">{n.title}</h3>
                      <p className="text-xs text-gray-400 mt-1 flex items-center gap-1"><CalendarDaysIcon className="w-3.5 h-3.5" /> {fmtFecha(n.published_at)}</p>
                      <p className="text-sm text-gray-500 line-clamp-2 mt-2">{n.body?.substring(0, 110)}…</p>
                    </div>
                  </Link>
                );
              })}
            </StaggerGrid>
            )}
          </div>
        </div>
      )}

      {/* ===== SECCIÓN 3 · EVENTOS (banda con degradado de color) ===== */}
      {secciones.eventos && (
        <div className="relative py-16 bg-white dark:bg-[#242424] border-t border-black/5 dark:border-white/10 transition-colors">
          <div className="max-w-7xl mx-auto px-4">
            <Reveal as="div" className="flex flex-col sm:flex-row sm:justify-between sm:items-end mb-10 gap-3">
              <div>
                <p className="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-green-700 dark:text-green-400 mb-2">
                  <span className="w-8 h-0.5 rounded-full brand-gradient-bar" /> Agenda cultural
                </p>
                <h2 className="font-serif text-3xl md:text-4xl font-extrabold text-gray-800 dark:text-white">Próximos eventos</h2>
              </div>
              <Link to="/eventos" className="text-green-700 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 text-sm font-semibold flex items-center gap-1 group">
                Ver todos <ArrowRightIcon className="w-4 h-4 group-hover:translate-x-1 transition" />
              </Link>
            </Reveal>

            {eventos.length === 0 ? (
              <div className="flex flex-col items-center justify-center text-center py-14 px-6 rounded-2xl border border-dashed border-green-300/60 dark:border-green-800/60 bg-green-50/40 dark:bg-green-900/10">
                <div className="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center mb-3">
                  <CalendarDaysIcon className="w-6 h-6 text-green-700 dark:text-green-400" />
                </div>
                <p className="text-gray-600 dark:text-gray-300 font-medium">Aún no hay eventos programados</p>
                <p className="text-sm text-gray-400 mt-1">Vuelve pronto para ver la agenda de San José de Chimbo.</p>
              </div>
            ) : (
            <StaggerGrid repeat className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {eventos.map((ev) => {
                const img = resolverImagen(ev.image_url);
                return (
                  <Link to={`/eventos/${ev.id}`} key={ev.id} className="group bg-white dark:bg-[#242424] rounded-2xl shadow-green-sm ring-1 ring-black/5 dark:ring-white/10 overflow-hidden hover:shadow-green-lg hover:-translate-y-1 transition-[transform,box-shadow] duration-300 ease-out">
                    <div className="relative h-44 overflow-hidden bg-gray-200 dark:bg-gray-700">
                      {img ? <LazyImg src={img} alt={ev.title} className="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105" />
                           : <div className="w-full h-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"><CalendarDaysIcon className="w-10 h-10 text-gray-400 dark:text-gray-500" /></div>}
                      {ev.categoria && <span className="absolute top-3 left-3 bg-green-700/95 backdrop-blur-sm text-white text-xs font-medium px-3 py-1 rounded-full ring-1 ring-inset ring-white/20">{ev.categoria}</span>}
                      {ev.starts_at && (
                        <div className="absolute top-3 right-3 bg-white rounded-xl shadow-lg text-center px-2.5 py-1.5 leading-none">
                          <div className="text-lg font-black text-green-700">{new Date(ev.starts_at).getDate()}</div>
                          <div className="text-[10px] font-bold uppercase text-gray-500">{new Date(ev.starts_at).toLocaleDateString('es-ES', { month: 'short' })}</div>
                        </div>
                      )}
                      <span className={`absolute bottom-3 left-3 flex items-center gap-1 text-white text-xs px-3 py-1 rounded-full font-semibold ${esEventoPasado(ev) ? 'bg-gray-600/80' : 'bg-emerald-500/90'}`}>
                        <span className="w-1.5 h-1.5 rounded-full bg-current" /> {esEventoPasado(ev) ? 'Evento pasado' : 'Evento actual'}
                      </span>
                    </div>
                    <div className="p-5">
                      <h3 className="font-bold text-lg text-gray-800 dark:text-white line-clamp-2 group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors">{ev.title}</h3>
                      <p className="text-xs text-gray-400 mt-1 flex items-center gap-1"><CalendarDaysIcon className="w-3.5 h-3.5" /> {fmtFecha(ev.starts_at)}</p>
                      {ev.description && <p className="text-sm text-gray-500 line-clamp-2 mt-2">{ev.description.substring(0, 110)}…</p>}
                    </div>
                  </Link>
                );
              })}
            </StaggerGrid>
            )}
          </div>
        </div>
      )}

      {/* ===== SECCIÓN · VISITAS + VIDEO/FOTOS + INICIAR SESIÓN ===== */}
      <div className="relative py-12 bg-gray-50 dark:bg-gray-800 border-t border-black/5 dark:border-white/10 transition-colors">
        <div className="max-w-7xl mx-auto px-4 grid md:grid-cols-2 lg:grid-cols-3 gap-8 items-center">

          {/* ── Columna 1: contador de visitas (animado) ── */}
          <Reveal className="text-center lg:text-left">
            <div className="inline-flex items-center gap-2 mb-2">
              <EyeIcon className="w-5 h-5 text-green-700 dark:text-green-400" />
              <h2 className="font-serif text-2xl font-extrabold text-gray-800 dark:text-white">Visitas</h2>
            </div>
            {visitas !== null ? (
              <p className="text-4xl md:text-5xl font-black text-gold-500 dark:text-gold-400 leading-none">
                <CountUp value={visitas} />
              </p>
            ) : (
              <div className="h-12 w-44 max-w-full mx-auto lg:mx-0 rounded-lg bg-gray-200 dark:bg-gray-700 animate-pulse" />
            )}
            <p className="text-gray-500 dark:text-gray-400 text-xs mt-2 leading-relaxed">
              personas han visitado el portal turístico de San José de Chimbo.
            </p>
          </Reveal>

          {/* ── Columna 2: video + collage de fotos del cantón ── */}
          <Reveal delay={100}>
            <div className="flex gap-3 items-stretch h-[300px] sm:h-[360px] md:h-[420px]">
              {/* Video vertical, autoplay silencioso en loop */}
              <div className="relative w-2/5 shrink-0 rounded-2xl overflow-hidden shadow-sm border border-black/5 dark:border-white/10 bg-black h-full group">
                <VideoDeferido
                  src={VIDEO_CHIMBO}
                  className="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                />
                <div className="pointer-events-none absolute inset-0 ring-1 ring-inset ring-white/10" />
              </div>

              {/* Collage de fotos: la iglesia arriba, estatua y vista abajo */}
              <div className="w-3/5 h-full grid grid-cols-2 grid-rows-2 gap-2">
                {FOTOS_CHIMBO.map((img, idx) => (
                  <img
                    key={img.url}
                    src={img.url}
                    alt={img.alt}
                    loading="lazy"
                    decoding="async"
                    className={`w-full h-full object-cover rounded-xl shadow-sm border border-black/5 dark:border-white/10 hover:scale-105 transition-transform duration-500 ease-out ${idx === 0 ? 'col-span-2' : ''}`}
                  />
                ))}
              </div>
            </div>
            <p className="text-gray-500 dark:text-gray-400 text-xs mt-2 text-center leading-relaxed">
              Imágenes y video de San José de Chimbo.
            </p>
          </Reveal>

          {/* ── Columna 3: acceso al panel de administración ── */}
          <Reveal delay={200} className="md:col-span-2 lg:col-span-1 max-w-md w-full mx-auto lg:mx-0">
            <AdminAccessCard />
          </Reveal>
        </div>
      </div>

      {/* Modal de detalle */}
      {selectedPlace && (
        <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-modal-backdrop" onClick={closeModal}>
          <div className="relative max-w-4xl w-full bg-white dark:bg-[#242424] rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] animate-modal-pop" onClick={(e) => e.stopPropagation()}>
            <button onClick={closeModal} aria-label="Cerrar" className="btn-press absolute top-4 right-4 z-20 bg-black/50 hover:bg-black/70 text-white rounded-full w-10 h-10 flex items-center justify-center"><XMarkIcon className="w-5 h-5" /></button>
            <div className="relative h-64 md:h-80">
              {resolverImagen(selectedPlace.imagen_url) ? (
                <img src={resolverImagen(selectedPlace.imagen_url)} alt={selectedPlace.nombre} className="w-full h-full object-cover" />
              ) : (
                <div className="w-full h-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"><PhotoIcon className="w-16 h-16 text-gray-400 dark:text-gray-500" /></div>
              )}
              <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent" />
              <div className="absolute bottom-4 left-4 flex flex-wrap gap-2">
                {selectedPlace.categoria && <span className="text-sm bg-green-700/80 text-white px-3 py-1 rounded-full">{selectedPlace.categoria}</span>}
              </div>
            </div>
            <div className="p-6 overflow-y-auto max-h-[calc(90vh-320px)]">
              <h2 className="text-2xl font-bold mb-2 text-gray-800 dark:text-white">{selectedPlace.nombre}</h2>
              <p className="text-gray-700 dark:text-gray-300 leading-relaxed text-sm mb-4 whitespace-pre-wrap">
                {selectedPlace.descripcion || selectedPlace.description || 'Sin descripción.'}
              </p>
              <div className="grid grid-cols-2 gap-3 mb-4">
                {selectedPlace.horario && <div className="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3"><p className="text-xs text-gray-500 dark:text-gray-400">Horario</p><p className="font-semibold text-sm text-gray-800 dark:text-white flex items-center gap-1"><ClockIcon className="w-4 h-4" /> {selectedPlace.horario}</p></div>}
                {selectedPlace.precio && <div className="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3"><p className="text-xs text-gray-500 dark:text-gray-400">Precio</p><p className="font-semibold text-sm text-gray-800 dark:text-white flex items-center gap-1"><BanknotesIcon className="w-4 h-4" /> {selectedPlace.precio}</p></div>}
                {selectedPlace.direccion && <div className="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3"><p className="text-xs text-gray-500 dark:text-gray-400">Dirección</p><p className="font-semibold text-sm text-gray-800 dark:text-white flex items-center gap-1"><MapPinIcon className="w-4 h-4" /> {selectedPlace.direccion}</p></div>}
                {selectedPlace.telefono && <div className="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3"><p className="text-xs text-gray-500 dark:text-gray-400">Contacto</p><p className="font-semibold text-sm text-gray-800 dark:text-white flex items-center gap-1"><PhoneIcon className="w-4 h-4" /> {selectedPlace.telefono}</p></div>}
              </div>
              <div className="flex gap-3">
                <Link to="/mapa" state={{ lat: selectedPlace.lat, lng: selectedPlace.lng, placeId: selectedPlace.id, nombre: selectedPlace.nombre }} className="btn-press flex-1 flex items-center justify-center gap-1.5 px-4 py-2.5 bg-green-700 hover:bg-green-800 text-white rounded-xl font-semibold text-sm text-center shadow-green-md"><MapIcon className="w-4 h-4" /> Ver en el mapa</Link>
                {selectedPlace.lat && selectedPlace.lng && (
                  <button onClick={() => window.open(`https://www.google.com/maps/dir/?api=1&destination=${selectedPlace.lat},${selectedPlace.lng}&travelmode=driving`, '_blank')} className="btn-press flex items-center gap-1.5 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold text-sm hover:bg-gray-200 dark:hover:bg-gray-600"><MapPinIcon className="w-4 h-4" /> Cómo llegar</button>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
