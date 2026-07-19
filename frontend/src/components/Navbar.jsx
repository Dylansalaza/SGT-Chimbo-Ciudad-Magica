import React, { useState, useEffect } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import ThemeToggle from './ThemeToggle';
import {
  HomeIcon,
  NewspaperIcon,
  PhotoIcon,
  MapIcon,
  ChartBarIcon,
  UserCircleIcon,
  XMarkIcon,
  ChevronDownIcon,
  SparklesIcon,
  MapPinIcon,
  BuildingStorefrontIcon,
  BuildingOffice2Icon,
} from '@heroicons/react/24/solid';

// Base del backend (para enlazar al panel admin de Laravel).
// OJO: el reemplazo va anclado al FINAL ($) — si se usa .replace('/api', '')
// a secas, y el dominio empieza con "api." (ej. api.midominio.com/api), el
// replace corta la PRIMERA aparición de "/api" (dentro de "//api...") en vez
// del sufijo, dejando una URL rota como "https:/.midominio.com/api".
const BACKEND_URL = import.meta.env.VITE_API_URL?.replace(/\/api$/, '') || 'http://127.0.0.1:3000';

// Lee el usuario logueado. La sesión vive en sessionStorage (se borra al
// CERRAR LA PESTAÑA); el login la guarda ahí a propósito. Se mantiene la
// lectura de localStorage como respaldo para sesiones antiguas aún no migradas
// (ver la migración en main.jsx).
const getCurrentUser = () => {
  const user = sessionStorage.getItem('user') || localStorage.getItem('user');
  return user ? JSON.parse(user) : null;
};

// Determina si el usuario logueado tiene permisos de administrador,
// para mostrar el enlace "Panel Admin" solo a quien corresponde.
const isAdmin = () => {
  const user = getCurrentUser();
  return user?.is_admin === true; // el backend devuelve is_admin
};

// Enlaces del menú de navegación público (mismo orden en escritorio y móvil).
// "Eventos" se reemplazó por el menú desplegable "Turismo" (ver TURISMO_MENU),
// que se inserta aparte en el orden porque no es un enlace simple.
const NAV_LINKS = [
  { to: '/',         label: 'Inicio',         Icon: HomeIcon },
  { to: '/noticias', label: 'Noticias',       Icon: NewspaperIcon },
  { to: '/galerias', label: 'Galerías',       Icon: PhotoIcon },
  { to: '/mapa',     label: 'Mapa Turístico', Icon: MapIcon },
];

// Submenú "Turismo": 4 preguntas frecuentes del turista. Cada una lleva a una
// vista y, cuando aplica, envía un `state` de navegación que el mapa (/mapa)
// interpreta para filtrar los marcadores:
//   • ¿Qué hacer?    → /eventos (agenda de eventos del cantón)
//   • ¿Cómo llegar?  → /mapa con TODOS los lugares atractivos (showAll)
//   • ¿Qué comer?    → /mapa filtrado a restaurantes y cafeterías (categoriaKeys)
//   • ¿Dónde dormir? → /mapa filtrado a hoteles, hostales y hosterías
// Las categoriaKeys son subcadenas SIN tildes: el mapa hace `includes()` sobre
// la categoría en minúsculas, así "hoster" calza con "Hostería" y "cafeter"
// con "Cafetería" sin depender del acento.
const TURISMO_MENU = [
  {
    to: '/eventos',
    label: '¿Qué hacer?',
    desc: 'Eventos y actividades del cantón',
    Icon: SparklesIcon,
  },
  {
    to: '/mapa',
    label: '¿Cómo llegar?',
    desc: 'Todos los lugares atractivos en el mapa',
    Icon: MapPinIcon,
    state: { showAll: true },
  },
  {
    to: '/mapa',
    label: '¿Qué comer?',
    desc: 'Restaurantes y cafeterías',
    Icon: BuildingStorefrontIcon,
    state: { categoriaKeys: ['restaurante', 'cafeter'] },
  },
  {
    to: '/mapa',
    label: '¿Dónde dormir?',
    desc: 'Hoteles, hostales y hosterías',
    Icon: BuildingOffice2Icon,
    state: { categoriaKeys: ['hotel', 'hostal', 'hoster'] },
  },
];

// ============================================================================
// COMPONENTE: Navbar
// Barra de navegación fija (fixed) presente en todas las páginas públicas
// (se oculta a sí misma en /admin, que usa su propio layout de Laravel).
// Cambia de estilo al hacer scroll, muestra el botón de modo oscuro y, si el
// usuario logueado es admin, un acceso directo al panel de Laravel. Incluye
// versión de escritorio (enlaces en fila) y versión móvil (menú hamburguesa).
// ============================================================================
export default function Navbar() {
  const navigate = useNavigate();
  const location = useLocation();
  const user = getCurrentUser();
  const authenticated = !!(localStorage.getItem('token') || sessionStorage.getItem('token'));
  const admin = isAdmin();

  // Estado para capturar el scroll
  const [isScrolled, setIsScrolled] = useState(false);
  // Estado del menú móvil (hamburguesa)
  const [menuAbierto, setMenuAbierto] = useState(false);
  // Estado del desplegable "Turismo" en escritorio (se abre al pasar el ratón
  // o al hacer clic; se cierra al elegir una opción o al cambiar de ruta).
  const [turismoAbierto, setTurismoAbierto] = useState(false);

  // Escuchar el evento de scroll
  useEffect(() => {
    const handleScroll = () => {
      if (window.scrollY > 20) {
        setIsScrolled(true);
      } else {
        setIsScrolled(false);
      }
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  // Cerrar el menú móvil y el desplegable "Turismo" al cambiar de ruta.
  // OJO: dependemos también de location.key (no solo de pathname) porque las
  // opciones del submenú "¿Qué comer?/¿Dónde dormir?" navegan siempre a /mapa
  // cambiando solo el `state`; sin location.key el efecto no se dispararía al
  // ir de una opción de /mapa a otra y el desplegable quedaría abierto.
  useEffect(() => {
    setMenuAbierto(false);
    setTurismoAbierto(false);
  }, [location.pathname, location.key]);

  // No mostrar navbar en rutas de administrador (Laravel)
  if (location.pathname.startsWith('/admin')) {
    return null;
  }

  // Marca el enlace de la página actual (inicio requiere match exacto; el
  // resto basta con que la ruta empiece por su prefijo, ej. /eventos/12).
  const isActive = (to) =>
    to === '/' ? location.pathname === '/' : location.pathname.startsWith(to);

  // "Turismo" se resalta como activo cuando estamos en cualquiera de sus
  // destinos: la agenda de eventos (/eventos) o el mapa turístico (/mapa).
  const turismoActivo = isActive('/eventos') || isActive('/mapa');

  // Render de un enlace simple del navbar de escritorio. Se extrae en una
  // función para poder intercalar el desplegable "Turismo" entre "Inicio" y
  // el resto de enlaces sin duplicar el marcado.
  const renderNavLink = (l) => {
    const active = isActive(l.to);
    return (
      <Link
        key={l.to}
        to={l.to}
        aria-current={active ? 'page' : undefined}
        className={`relative flex items-center gap-1.5 whitespace-nowrap px-3 py-2 rounded-lg text-sm transition-colors duration-200 ${
          active ? 'text-white' : 'text-green-100/80 hover:text-white hover:bg-white/5'
        }`}
      >
        <l.Icon className={`w-4 h-4 transition-colors ${active ? 'text-gold-400' : ''}`} /> {l.label}
        {/* Indicador de página activa: subrayado dorado */}
        <span className={`pointer-events-none absolute left-3 right-3 -bottom-0.5 h-0.5 rounded-full brand-gradient-bar origin-left transition-transform duration-200 ease-out ${active ? 'scale-x-100' : 'scale-x-0'}`} />
      </Link>
    );
  };

  // Render de un enlace simple del navbar MÓVIL (menú hamburguesa).
  const renderMobileLink = (l) => {
    const active = isActive(l.to);
    return (
      <Link
        key={l.to}
        to={l.to}
        aria-current={active ? 'page' : undefined}
        className={`flex items-center gap-2 px-3 py-2.5 rounded-lg transition-colors font-semibold ${
          active
            ? 'bg-white/10 text-white border-l-2 border-gold-400'
            : 'text-green-100/80 hover:bg-white/10 hover:text-white'
        }`}
      >
        <l.Icon className={`w-4 h-4 ${active ? 'text-gold-400' : ''}`} /> {l.label}
      </Link>
    );
  };

  // Cierra la sesión: borra las credenciales guardadas y redirige al login
  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    sessionStorage.removeItem('token');
    sessionStorage.removeItem('user');
    navigate('/');
  };

  return (
    <nav className={`fixed top-0 left-0 right-0 text-white z-50 transition-all duration-500 ease-out ${
      isScrolled
        ? 'bg-green-950/70 backdrop-blur-md border-b border-gold-400/20 shadow-green-md'
        : 'bg-gradient-to-b from-green-950 to-green-950/95'
    }`}>
      <div className="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">

          {/* LOGO Y TÍTULO */}
          <Link to="/" className="group flex items-center gap-2.5 shrink-0" aria-label="SGT Chimbo — Gestión Turística">
            {/* Marca: monograma en un sello con los colores del cantón */}
            <span className="grid place-items-center w-9 h-9 rounded-xl brand-gradient-bg ring-1 ring-inset ring-gold-400/50 shadow-green-md text-white font-serif font-black text-base leading-none transition-transform duration-200 ease-out group-hover:scale-105 group-hover:rotate-3">
              C
            </span>
            <div className="flex flex-col leading-none">
              <span className="font-extrabold italic text-white text-xl tracking-tight">SGT</span>
              <span className="font-extrabold text-gold-400 text-sm tracking-wide leading-tight">CHIMBO</span>
              <span className="text-[8px] font-semibold tracking-[0.3em] text-green-200/80 mt-1">GESTIÓN TURÍSTICA</span>
            </div>
          </Link>

          {/* ENLACES DE NAVEGACIÓN — solo en pantallas grandes */}
          <div className="hidden lg:flex items-center space-x-1 font-semibold">
            {/* Inicio */}
            {renderNavLink(NAV_LINKS[0])}

            {/* 🧭 MENÚ DESPLEGABLE "TURISMO" (reemplaza al antiguo "Eventos").
                Se abre al pasar el ratón por encima o al hacer clic. */}
            <div
              className="relative"
              onMouseEnter={() => setTurismoAbierto(true)}
              onMouseLeave={() => setTurismoAbierto(false)}
              onFocus={() => setTurismoAbierto(true)}
              onBlur={(e) => { if (!e.currentTarget.contains(e.relatedTarget)) setTurismoAbierto(false); }}
            >
              <button
                type="button"
                onClick={() => setTurismoAbierto(true)}
                aria-haspopup="true"
                aria-expanded={turismoAbierto}
                className={`relative flex items-center gap-1.5 whitespace-nowrap px-3 py-2 rounded-lg text-sm transition-colors duration-200 ${
                  turismoActivo ? 'text-white' : 'text-green-100/80 hover:text-white hover:bg-white/5'
                }`}
              >
                <MapIcon className={`w-4 h-4 transition-colors ${turismoActivo ? 'text-gold-400' : ''}`} /> Turismo
                <ChevronDownIcon className={`w-3.5 h-3.5 transition-transform duration-200 ${turismoAbierto ? 'rotate-180' : ''}`} />
                <span className={`pointer-events-none absolute left-3 right-3 -bottom-0.5 h-0.5 rounded-full brand-gradient-bar origin-left transition-transform duration-200 ease-out ${turismoActivo ? 'scale-x-100' : 'scale-x-0'}`} />
              </button>

              {/* Panel del desplegable */}
              <div
                className={`absolute left-0 top-full pt-2 w-72 transition-all duration-200 origin-top ${
                  turismoAbierto
                    ? 'opacity-100 visible translate-y-0'
                    : 'opacity-0 invisible -translate-y-1 pointer-events-none'
                }`}
              >
                <div className="bg-green-950/95 backdrop-blur-md border border-gold-400/20 rounded-xl shadow-green-md overflow-hidden p-1.5">
                  {TURISMO_MENU.map((item) => (
                    <Link
                      key={item.label}
                      to={item.to}
                      state={item.state}
                      onClick={() => setTurismoAbierto(false)}
                      className="flex items-start gap-3 px-3 py-2.5 rounded-lg text-green-100/90 hover:bg-white/10 hover:text-white transition-colors"
                    >
                      <item.Icon className="w-5 h-5 mt-0.5 shrink-0 text-gold-400" />
                      <span className="flex flex-col leading-tight">
                        <span className="text-sm font-semibold">{item.label}</span>
                        <span className="text-xs text-green-200/70">{item.desc}</span>
                      </span>
                    </Link>
                  ))}
                </div>
              </div>
            </div>

            {/* Resto de enlaces (Noticias, Galerías, Mapa Turístico) */}
            {NAV_LINKS.slice(1).map(renderNavLink)}

            {/* Botón al panel de administración (Laravel/Blade) - solo admin.
                Usamos <a> normal porque /admin NO es una ruta de React. */}
            {admin && (
              <div className="flex items-center space-x-4 ml-3 pl-3 border-l border-white/15">
                <a href={`${BACKEND_URL}/login`} className="flex items-center gap-1.5 whitespace-nowrap px-3 py-2 rounded-lg text-gold-300 hover:text-gold-200 hover:bg-white/5 text-sm transition-colors">
                  <ChartBarIcon className="w-4 h-4" /> Panel Admin
                </a>
              </div>
            )}
          </div>

          {/* SESIÓN — solo en pantallas grandes */}
          <div className="hidden lg:flex items-center space-x-3">
            <ThemeToggle />
            {authenticated ? (
              <div className="flex items-center space-x-3">
                <span className="flex items-center gap-1.5 text-green-100/80 text-sm whitespace-nowrap">
                  <UserCircleIcon className="w-5 h-5 text-gold-400" /> {admin ? 'Administrador' : user?.name || 'Usuario'}
                </span>
                <button
                  onClick={handleLogout}
                  className="btn-press px-3 py-1.5 rounded-lg bg-red-500/90 text-white hover:bg-red-600 text-sm font-medium whitespace-nowrap"
                >
                  Cerrar Sesión
                </button>
              </div>
            ) : null}
          </div>

          {/* Toggle oscuro + HAMBURGUESA — pantallas pequeñas/medianas */}
          <div className="lg:hidden flex items-center gap-2 shrink-0">
            <ThemeToggle />
            <button
              onClick={() => setMenuAbierto((o) => !o)}
              aria-label={menuAbierto ? 'Cerrar menú' : 'Abrir menú'}
              aria-expanded={menuAbierto}
              className="btn-press flex items-center justify-center w-10 h-10 rounded-lg text-white hover:bg-white/10"
            >
              {menuAbierto ? (
                <XMarkIcon className="w-6 h-6" />
              ) : (
                <div className="space-y-1.5">
                  <span className="block w-6 h-0.5 bg-white"></span>
                  <span className="block w-6 h-0.5 bg-white"></span>
                  <span className="block w-6 h-0.5 bg-white"></span>
                </div>
              )}
            </button>
          </div>
        </div>

        {/* MENÚ MÓVIL DESPLEGABLE */}
        {menuAbierto && (
          <div className="lg:hidden pb-4 border-t border-white/10 pt-3 space-y-1 animate-fade-in-up">
            {/* Inicio */}
            {renderMobileLink(NAV_LINKS[0])}

            {/* 🧭 Sección "Turismo" con sus 4 preguntas (siempre visible en móvil) */}
            <div className="pt-1">
              <p className="flex items-center gap-2 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-gold-400/90">
                <MapIcon className="w-4 h-4" /> Turismo
              </p>
              <div className="pl-2 space-y-1 border-l border-white/10 ml-3">
                {TURISMO_MENU.map((item) => (
                  <Link
                    key={item.label}
                    to={item.to}
                    state={item.state}
                    className="flex items-start gap-2.5 px-3 py-2 rounded-lg text-green-100/80 hover:bg-white/10 hover:text-white transition-colors"
                  >
                    <item.Icon className="w-4 h-4 mt-0.5 shrink-0 text-gold-400" />
                    <span className="flex flex-col leading-tight">
                      <span className="text-sm font-semibold">{item.label}</span>
                      <span className="text-[11px] text-green-200/60">{item.desc}</span>
                    </span>
                  </Link>
                ))}
              </div>
            </div>

            {/* Resto de enlaces (Noticias, Galerías, Mapa Turístico) */}
            {NAV_LINKS.slice(1).map(renderMobileLink)}

            {admin && (
              <a
                href={`${BACKEND_URL}/login`}
                className="flex items-center gap-2 px-3 py-2.5 rounded-lg text-gold-300 hover:bg-white/10 hover:text-gold-200 transition-colors font-semibold"
              >
                <ChartBarIcon className="w-4 h-4" /> Panel Admin
              </a>
            )}

            <div className="pt-2 mt-2 border-t border-white/10">
              {authenticated ? (
                <div className="flex items-center justify-between px-3 py-2">
                  <span className="flex items-center gap-1.5 text-green-100/80 text-sm">
                    <UserCircleIcon className="w-5 h-5 text-gold-400" /> {admin ? 'Administrador' : user?.name || 'Usuario'}
                  </span>
                  <button
                    onClick={handleLogout}
                    className="btn-press px-3 py-1.5 rounded-lg bg-red-500/90 text-white hover:bg-red-600 text-sm font-medium"
                  >
                    Cerrar Sesión
                  </button>
                </div>
              ) : null}
            </div>
          </div>
        )}
      </div>
    </nav>
  );
}