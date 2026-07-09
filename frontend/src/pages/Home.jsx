import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
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
  StarIcon,
  ArrowRightIcon,
  PhotoIcon,
  ClockIcon,
  BanknotesIcon,
  NewspaperIcon,
  CalendarDaysIcon,
  XMarkIcon,
  PhoneIcon,
} from '@heroicons/react/24/solid';

const API_URL     = import.meta.env.VITE_API_URL || 'http://127.0.0.1:3000/api';
const LARAVEL_URL = API_URL.replace('/api', '');

// Normaliza rutas de imagen (igual criterio que el resto de la app)
function resolverImagen(url) {
  if (!url) return null;
  const i = url.indexOf('/storage/');
  if (i !== -1) return LARAVEL_URL + url.slice(i);
  if (url.startsWith('http')) return url;
  if (url.startsWith('/'))    return LARAVEL_URL + url;
  return LARAVEL_URL + '/storage/' + url;
}

// Carrusel por defecto con imágenes reales de San José de Chimbo, Ecuador
// Fuente: Wikimedia Commons — licencia libre
const CARRUSEL_DEFAULT = [
  {
    url: 'https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/Chimbo%2C_Ecuador.JPG&width=1400',
    title: 'San José de Chimbo',
    subtitle: 'Naturaleza, cultura y aventura en los Andes del Ecuador',
  },
  {
    url: 'https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/Parque_Central_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1400',
    title: 'Parque Central',
    subtitle: 'El corazón histórico de nuestra ciudad',
  },
  {
    url: 'https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/El_Torre%C3%B3n_San_Jos%C3%A9_de_Chimbo.jpg&width=1400',
    title: 'El Torreón',
    subtitle: 'Patrimonio cultural de San José de Chimbo',
  },
  {
    url: 'https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/Vista_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1400',
    title: 'Vista panorámica',
    subtitle: 'Un destino lleno de historia y tradición',
  },
  {
    url: 'https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/Calle_Tres_de_Marzo_en_San_Jos%C3%A9_de_Chimbo.jpg&width=1400',
    title: 'Calle Tres de Marzo',
    subtitle: 'Recorre las calles con historia de Chimbo',
  },
];

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
  const [destacados, setDestacados]     = useState([]);                 // Lugares marcados como "destacado"
  const [noticias, setNoticias]         = useState([]);                 // Últimas noticias publicadas
  const [eventos, setEventos]           = useState([]);                 // Próximos eventos programados
  // Interruptores para mostrar/ocultar cada sección (configurables desde el admin)
  const [secciones, setSecciones]       = useState({ destacados: true, noticias: true, eventos: true });
  const [selectedPlace, setSelectedPlace] = useState(null); // Lugar destacado abierto en el modal de detalle

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

  // Carga inicial: registra la visita (para las estadísticas del admin) y
  // trae todo el contenido dinámico del Home desde un único endpoint (/home).
  useEffect(() => {
    // Registrar visita anónima (no requiere login, solo cuenta para reportes)
    fetch(`${API_URL}/registro-visita`, { method: 'POST' }).catch(() => {});

    const cargar = async () => {
      try {
        const homeResp = await fetch(`${API_URL}/home`);
        const data     = await homeResp.json();

        // Cada campo solo se sobrescribe si el backend efectivamente lo envió,
        // así se conservan los valores por defecto cuando el admin no configuró algo.
        if (data.welcome_title) setWelcomeTitle(data.welcome_title);
        if (data.welcome_text)  setWelcomeText(data.welcome_text);
        if (Array.isArray(data.carousel) && data.carousel.length) {
          setCarousel(data.carousel.map(s => ({ ...s, url: resolverImagen(s.url) })));
        }
        if (Array.isArray(data.destacados)) setDestacados(data.destacados);
        if (Array.isArray(data.noticias))  setNoticias(data.noticias);
        if (Array.isArray(data.eventos))   setEventos(data.eventos);
        if (data.secciones) setSecciones({ destacados: true, noticias: true, eventos: true, ...data.secciones });
      } catch (err) {
        console.error('Error cargando el Home:', err);
      }
    };
    cargar();
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

  return (
    <div className="min-h-screen bg-gradient-to-b from-gray-50 to-white dark:from-[#242424] dark:to-gray-800">

      {/* Carrusel principal */}
      <div className="bg-black">
        <Swiper
          modules={[Autoplay, Pagination, Navigation, EffectFade]}
          effect="fade"
          autoplay={{ delay: 4000, disableOnInteraction: false }}
          pagination={{ clickable: true, dynamicBullets: true }}
          navigation={true}
          loop={carousel.length > 1}
          className="h-[420px] sm:h-[480px] md:h-[560px] lg:h-[640px] w-full"
        >
          {carousel.map((image, idx) => (
            <SwiperSlide key={idx}>
              <div className="relative h-full w-full">
                <img src={image.url} alt={image.title || ''} className="w-full h-full object-cover" loading={idx === 0 ? 'eager' : 'lazy'} />
                <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
                <div className="absolute bottom-0 left-0 right-0 p-8 md:p-12 text-center text-white">
                  <div className="max-w-4xl mx-auto">
                    {image.title && <h2 className="font-serif text-4xl md:text-6xl font-bold mb-4">{image.title}</h2>}
                    {image.subtitle && <p className="text-lg md:text-2xl opacity-90">{image.subtitle}</p>}
                    <Link to="/mapa" className="inline-flex items-center gap-2 mt-6 px-8 py-3 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full text-white font-semibold hover:scale-105 transition transform duration-300 shadow-lg">
                      <MapIcon className="w-5 h-5" /> Explorar ahora
                    </Link>
                  </div>
                </div>
              </div>
            </SwiperSlide>
          ))}
        </Swiper>
      </div>

      {/* Bienvenida */}
      <div className="relative py-16 md:py-20 overflow-hidden border-t border-black/5 dark:border-white/10">
        <div className="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-purple-500/10 blur-3xl"></div>
        <div className="relative max-w-7xl mx-auto px-4">
          <div className="grid md:grid-cols-2 gap-10 lg:gap-14 items-center">

            {/* ── Columna izquierda: texto ── */}
            <div className="text-left">
              <p className="text-xs font-bold uppercase tracking-[0.3em] text-blue-500 mb-2">Bienvenido a</p>
              <h1 className="font-serif text-4xl md:text-5xl font-bold mb-4 text-black dark:text-white">
                {welcomeTitle}
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

            {/* ── Columna derecha: collage de fotos ── */}
            <div className="grid grid-cols-2 grid-rows-2 gap-3 h-[320px] sm:h-[380px] md:h-[440px]">
              <img
                src="https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/Chimbo%2C_Ecuador.JPG&width=800"
                alt="San José de Chimbo"
                loading="lazy"
                decoding="async"
                className="row-span-2 h-full w-full object-cover rounded-2xl shadow-xl"
              />
              <img
                src="https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/Parque_Central_de_San_Jos%C3%A9_de_Chimbo.jpg&width=800"
                alt="Parque Central de San José de Chimbo"
                loading="lazy"
                decoding="async"
                className="h-full w-full object-cover rounded-2xl shadow-xl"
              />
              <img
                src="https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/El_Torre%C3%B3n_San_Jos%C3%A9_de_Chimbo.jpg&width=800"
                alt="El Torreón, San José de Chimbo"
                loading="lazy"
                decoding="async"
                className="h-full w-full object-cover rounded-2xl shadow-xl"
              />
            </div>

          </div>
        </div>
      </div>

      {/* ===== ÍCONO DISTINTIVO: IGLESIA MATRIZ DE SAN JOSÉ DE CHIMBO ===== */}
      <div className="relative py-16 border-t border-black/5 dark:border-white/10">
        <div className="max-w-7xl mx-auto px-4">
          <div className="relative h-[320px] md:h-[400px] rounded-3xl overflow-hidden shadow-xl">
            <img
              src="https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/Iglesia_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1400"
              alt="Iglesia Matriz de San José de Chimbo"
              className="w-full h-full object-cover object-center"
              onError={(e) => {
                e.target.src = 'https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/Parque_Central_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1400';
              }}
            />
            {/* Gradiente oscuro en la parte inferior */}
            <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent" />
            {/* Franja de color arriba */}
            <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-yellow-400 via-white to-yellow-400" />
            {/* Texto sobre la imagen */}
            <div className="absolute bottom-0 left-0 right-0 p-8 md:p-12">
              <div className="max-w-3xl">
                <div className="flex items-center gap-3 mb-3">
                  <BuildingLibraryIcon className="text-yellow-400 w-8 h-8" />
                  <span className="text-xs font-bold uppercase tracking-[0.25em] text-yellow-300">Patrimonio histórico</span>
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
          </div>
        </div>
      </div>

      {/* ===== SECCIÓN 1 · LUGARES DESTACADOS (banda blanca, limpia) ===== */}
      {secciones.destacados && (
      <div className="relative py-16 bg-white dark:bg-[#242424] border-t border-black/5 dark:border-white/10 transition-colors">
        <div className="relative max-w-7xl mx-auto px-4">
          <div className="flex flex-col sm:flex-row sm:justify-between sm:items-end mb-10 gap-3">
            <div>
              <p className="text-xs font-bold uppercase tracking-[0.3em] text-blue-500 mb-1">Descubre</p>
              <h2 className="font-serif text-3xl md:text-4xl font-extrabold text-gray-800 dark:text-white flex items-center gap-2">
                <StarIcon className="w-7 h-7 text-blue-500" /> Lugares Destacados
              </h2>
              <div className="w-20 h-1.5 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full mt-3"></div>
            </div>
            <Link to="/mapa" className="text-blue-500 hover:text-blue-600 text-sm font-semibold flex items-center gap-1 group">
              Ver todos en el mapa <ArrowRightIcon className="w-4 h-4 group-hover:translate-x-1 transition" />
            </Link>
          </div>

          {destacados.length === 0 ? (
            <div className="text-center py-12 text-gray-400">
              Aún no hay lugares destacados. (Márcalos desde el panel de administración.)
            </div>
          ) : (
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {destacados.map((lugar) => {
                const img = resolverImagen(lugar.imagen_url);
                return (
                  <div key={lugar.id} className="group relative bg-white dark:bg-[#242424] rounded-2xl shadow-xl dark:shadow-black/30 border border-transparent dark:border-gray-700 overflow-hidden hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer" onClick={() => openModal(lugar)}>
                    <div className="relative h-56 overflow-hidden bg-gray-200 dark:bg-gray-700">
                      {img ? (
                        <img src={img} alt={lugar.nombre} loading="lazy" decoding="async" className="w-full h-full object-cover" />
                      ) : (
                        <div className="w-full h-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"><PhotoIcon className="w-12 h-12 text-gray-400 dark:text-gray-500" /></div>
                      )}
                      <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                      {lugar.categoria && (
                        <span className="absolute top-3 left-3 bg-blue-600 text-white text-xs px-3 py-1 rounded-full shadow-lg">
                          {lugar.categoria}
                        </span>
                      )}
                    </div>
                    <div className="p-4">
                      <h3 className="font-bold text-lg text-gray-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{lugar.nombre}</h3>
                      <div className="flex justify-between items-center mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {lugar.horario && <span className="flex items-center gap-1"><ClockIcon className="w-3.5 h-3.5" /> {lugar.horario}</span>}
                        {lugar.precio && <span className="flex items-center gap-1"><BanknotesIcon className="w-3.5 h-3.5" /> {lugar.precio}</span>}
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>
      )}

      {/* ===== SECCIÓN 2 · NOTICIAS (banda oscura, estilo prensa) ===== */}
      {secciones.noticias && (
        <div className="relative py-16 overflow-hidden bg-gradient-to-b from-gray-50 to-white dark:from-[#242424] dark:to-gray-800 border-t border-black/5 dark:border-white/10 transition-colors">
          <div className="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-purple-500/10 blur-3xl"></div>
          <div className="relative max-w-7xl mx-auto px-4">
            <div className="flex flex-col sm:flex-row sm:justify-between sm:items-end mb-10 gap-3">
              <div>
                <p className="text-xs font-bold uppercase tracking-[0.3em] text-rose-500 dark:text-rose-400 mb-1">Actualidad</p>
                <h2 className="font-serif text-3xl md:text-4xl font-extrabold text-gray-800 dark:text-white flex items-center gap-2"><NewspaperIcon className="w-7 h-7 text-rose-500" /> Noticias recientes</h2>
                <div className="w-20 h-1.5 bg-rose-500 rounded-full mt-3"></div>
              </div>
              <Link to="/noticias" className="text-rose-600 hover:text-rose-700 dark:text-rose-300 dark:hover:text-white text-sm font-semibold flex items-center gap-1 group">
                Ver todas <ArrowRightIcon className="w-4 h-4 group-hover:translate-x-1 transition" />
              </Link>
            </div>

            {noticias.length === 0 ? (
              <div className="text-center py-12 text-gray-400 dark:text-blue-200/70">
                Aún no hay noticias publicadas. Vuelve pronto para ver las novedades de Chimbo.
              </div>
            ) : (
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {noticias.map((n) => {
                const img = resolverImagen(n.image_url);
                return (
                  <Link to="/noticias" key={n.id} className="group bg-white dark:bg-[#242424] rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                    <div className="relative h-44 overflow-hidden bg-gray-200 dark:bg-gray-700">
                      {img ? <img src={img} alt={n.title} loading="lazy" decoding="async" className="w-full h-full object-cover" />
                           : <div className="w-full h-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"><NewspaperIcon className="w-10 h-10 text-gray-400 dark:text-gray-500" /></div>}
                      {n.categoria && <span className="absolute top-3 left-3 bg-blue-600/90 text-white text-xs px-3 py-1 rounded-full">{n.categoria}</span>}
                      <span className={`absolute top-3 right-3 flex items-center gap-1 text-white text-xs px-3 py-1 rounded-full font-semibold ${esNoticiaPasada(n) ? 'bg-gray-600/80' : 'bg-emerald-500/90'}`}>
                        <span className="w-1.5 h-1.5 rounded-full bg-current" /> {esNoticiaPasada(n) ? 'Pasada' : 'Actual'}
                      </span>
                    </div>
                    <div className="p-5">
                      <h3 className="font-bold text-lg text-gray-800 dark:text-white line-clamp-2 group-hover:text-blue-600 transition-colors">{n.title}</h3>
                      <p className="text-xs text-gray-400 mt-1 flex items-center gap-1"><CalendarDaysIcon className="w-3.5 h-3.5" /> {fmtFecha(n.published_at)}</p>
                      <p className="text-sm text-gray-500 line-clamp-2 mt-2">{n.body?.substring(0, 110)}…</p>
                    </div>
                  </Link>
                );
              })}
            </div>
            )}
          </div>
        </div>
      )}

      {/* ===== SECCIÓN 3 · EVENTOS (banda con degradado de color) ===== */}
      {secciones.eventos && (
        <div className="relative py-16 bg-white dark:bg-[#242424] border-t border-black/5 dark:border-white/10 transition-colors">
          <div className="max-w-7xl mx-auto px-4">
            <div className="flex flex-col sm:flex-row sm:justify-between sm:items-end mb-10 gap-3">
              <div>
                <p className="text-xs font-bold uppercase tracking-[0.3em] text-purple-500 dark:text-purple-300 mb-1">Agenda</p>
                <h2 className="font-serif text-3xl md:text-4xl font-extrabold text-gray-800 dark:text-white flex items-center gap-2"><CalendarDaysIcon className="w-7 h-7 text-purple-500" /> Próximos eventos</h2>
                <div className="w-20 h-1.5 bg-gradient-to-r from-purple-500 to-blue-500 rounded-full mt-3"></div>
              </div>
              <Link to="/eventos" className="text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 text-sm font-semibold flex items-center gap-1 group">
                Ver todos <ArrowRightIcon className="w-4 h-4 group-hover:translate-x-1 transition" />
              </Link>
            </div>

            {eventos.length === 0 ? (
              <div className="text-center py-12 text-gray-400 dark:text-purple-200/70">
                Aún no hay eventos programados. Vuelve pronto para ver la agenda de San José de Chimbo.
              </div>
            ) : (
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {eventos.map((ev) => {
                const img = resolverImagen(ev.image_url);
                return (
                  <Link to="/eventos" key={ev.id} className="group bg-white dark:bg-[#242424] rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                    <div className="relative h-44 overflow-hidden bg-gray-200 dark:bg-gray-700">
                      {img ? <img src={img} alt={ev.title} loading="lazy" decoding="async" className="w-full h-full object-cover" />
                           : <div className="w-full h-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"><CalendarDaysIcon className="w-10 h-10 text-gray-400 dark:text-gray-500" /></div>}
                      {ev.categoria && <span className="absolute top-3 left-3 bg-purple-600/90 text-white text-xs px-3 py-1 rounded-full">{ev.categoria}</span>}
                      {ev.starts_at && (
                        <div className="absolute top-3 right-3 bg-white rounded-xl shadow-lg text-center px-2.5 py-1.5 leading-none">
                          <div className="text-lg font-black text-purple-600">{new Date(ev.starts_at).getDate()}</div>
                          <div className="text-[10px] font-bold uppercase text-gray-500">{new Date(ev.starts_at).toLocaleDateString('es-ES', { month: 'short' })}</div>
                        </div>
                      )}
                      <span className={`absolute bottom-3 left-3 flex items-center gap-1 text-white text-xs px-3 py-1 rounded-full font-semibold ${esEventoPasado(ev) ? 'bg-gray-600/80' : 'bg-emerald-500/90'}`}>
                        <span className="w-1.5 h-1.5 rounded-full bg-current" /> {esEventoPasado(ev) ? 'Evento pasado' : 'Evento actual'}
                      </span>
                    </div>
                    <div className="p-5">
                      <h3 className="font-bold text-lg text-gray-800 dark:text-white line-clamp-2 group-hover:text-blue-600 transition-colors">{ev.title}</h3>
                      <p className="text-xs text-gray-400 mt-1 flex items-center gap-1"><CalendarDaysIcon className="w-3.5 h-3.5" /> {fmtFecha(ev.starts_at)}</p>
                      {ev.description && <p className="text-sm text-gray-500 line-clamp-2 mt-2">{ev.description.substring(0, 110)}…</p>}
                    </div>
                  </Link>
                );
              })}
            </div>
            )}
          </div>
        </div>
      )}

      {/* Modal de detalle */}
      {selectedPlace && (
        <div className="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4" onClick={closeModal}>
          <div className="relative max-w-4xl w-full bg-white dark:bg-[#242424] rounded-2xl shadow-2xl overflow-hidden max-h-[90vh]" onClick={(e) => e.stopPropagation()}>
            <button onClick={closeModal} className="absolute top-4 right-4 z-20 bg-black/50 hover:bg-black/70 text-white rounded-full w-10 h-10 flex items-center justify-center"><XMarkIcon className="w-5 h-5" /></button>
            <div className="relative h-64 md:h-80">
              {resolverImagen(selectedPlace.imagen_url) ? (
                <img src={resolverImagen(selectedPlace.imagen_url)} alt={selectedPlace.nombre} className="w-full h-full object-cover" />
              ) : (
                <div className="w-full h-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"><PhotoIcon className="w-16 h-16 text-gray-400 dark:text-gray-500" /></div>
              )}
              <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent" />
              <div className="absolute bottom-4 left-4 flex flex-wrap gap-2">
                {selectedPlace.categoria && <span className="text-sm bg-blue-500/80 text-white px-3 py-1 rounded-full">{selectedPlace.categoria}</span>}
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
                <Link to="/mapa" className="flex-1 flex items-center justify-center gap-1.5 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold text-sm transition text-center"><MapIcon className="w-4 h-4" /> Ver en el mapa</Link>
                {selectedPlace.lat && selectedPlace.lng && (
                  <button onClick={() => window.open(`https://maps.google.com/?q=${selectedPlace.lat},${selectedPlace.lng}`, '_blank')} className="flex items-center gap-1.5 px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold text-sm hover:bg-gray-300 dark:hover:bg-gray-600 transition"><MapPinIcon className="w-4 h-4" /> Google Maps</button>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
