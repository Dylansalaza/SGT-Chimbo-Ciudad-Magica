import React, { useState, useEffect } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import ThemeToggle from './ThemeToggle';
import {
  HomeIcon,
  CalendarDaysIcon,
  NewspaperIcon,
  PhotoIcon,
  MapIcon,
  ChartBarIcon,
  UserCircleIcon,
  XMarkIcon,
} from '@heroicons/react/24/solid';

// Base del backend (para enlazar al panel admin de Laravel)
const BACKEND_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://127.0.0.1:3000';

// Lee el usuario logueado (guardado en localStorage al iniciar sesión)
const getCurrentUser = () => {
  // El login guarda la clave 'user'. Si el usuario NO marcó "Recordarme",
  // la sesión vive en sessionStorage (se borra al cerrar la pestaña).
  const user = localStorage.getItem('user') || sessionStorage.getItem('user');
  return user ? JSON.parse(user) : null;
};

// Determina si el usuario logueado tiene permisos de administrador,
// para mostrar el enlace "Panel Admin" solo a quien corresponde.
const isAdmin = () => {
  const user = getCurrentUser();
  return user?.is_admin === true; // el backend devuelve is_admin
};

// Enlaces del menú de navegación público (mismo orden en escritorio y móvil)
const NAV_LINKS = [
  { to: '/',        label: 'Inicio',         Icon: HomeIcon },
  { to: '/eventos', label: 'Eventos',        Icon: CalendarDaysIcon },
  { to: '/noticias', label: 'Noticias',      Icon: NewspaperIcon },
  { to: '/galerias', label: 'Galerías',      Icon: PhotoIcon },
  { to: '/mapa',    label: 'Mapa Turístico', Icon: MapIcon },
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

  // Cerrar el menú móvil al cambiar de ruta
  useEffect(() => {
    setMenuAbierto(false);
  }, [location.pathname]);

  // No mostrar navbar en rutas de administrador (Laravel)
  if (location.pathname.startsWith('/admin')) {
    return null;
  }

  // Cierra la sesión: borra las credenciales guardadas y redirige al login
  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    sessionStorage.removeItem('token');
    sessionStorage.removeItem('user');
    navigate('/');
  };

  return (
    <nav className={`fixed top-0 left-0 right-0 text-white shadow-md z-50 transition-all duration-500 ${
      isScrolled
        ? 'bg-black/60 backdrop-blur-md border-b border-white/10'
        : 'bg-black'
    }`}>
      <div className="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">

          {/* LOGO Y TÍTULO */}
          <Link to="/" className="flex items-center shrink-0">
            <img src="/media/logo/logo-horizontal-dark.svg" alt="SGT Chimbo — Sistema de Gestión Turístico" className="h-10 w-auto" />
          </Link>

          {/* ENLACES DE NAVEGACIÓN — solo en pantallas grandes */}
          <div className="hidden lg:flex items-center space-x-8 font-semibold">
            {NAV_LINKS.map((l) => (
              <Link key={l.to} to={l.to} className="flex items-center gap-1.5 whitespace-nowrap text-gray-200 hover:text-emerald-400 transition-colors">
                <l.Icon className="w-4 h-4" /> {l.label}
              </Link>
            ))}

            {/* Botón al panel de administración (Laravel/Blade) - solo admin.
                Usamos <a> normal porque /admin NO es una ruta de React. */}
            {admin && (
              <div className="flex items-center space-x-4 ml-4 pl-4 border-l border-white/20">
                <a href={`${BACKEND_URL}/admin`} className="flex items-center gap-1.5 whitespace-nowrap text-yellow-400 hover:text-yellow-300 text-sm">
                  <ChartBarIcon className="w-4 h-4" /> Panel Admin
                </a>
              </div>
            )}
          </div>

          {/* SESIÓN — solo en pantallas grandes */}
          <div className="hidden lg:flex items-center space-x-4">
            <ThemeToggle />
            {authenticated ? (
              <div className="flex items-center space-x-4">
                <span className="flex items-center gap-1.5 text-gray-300 text-sm whitespace-nowrap">
                  <UserCircleIcon className="w-5 h-5" /> {admin ? 'Administrador' : user?.name || 'Usuario'}
                </span>
                <button
                  onClick={handleLogout}
                  className="px-3 py-1 rounded bg-red-500 text-white hover:bg-red-600 text-sm font-medium transition whitespace-nowrap"
                >
                  Cerrar Sesión
                </button>
              </div>
            ) : null}
          </div>

          {/* Toggle oscuro + BOTÓN HAMBURGUESA — solo en pantallas pequeñas/medianas */}
          <div className="lg:hidden flex items-center gap-2 shrink-0">
            <ThemeToggle />
            <button
              onClick={() => setMenuAbierto((o) => !o)}
              aria-label={menuAbierto ? 'Cerrar menú' : 'Abrir menú'}
              aria-expanded={menuAbierto}
              className="flex items-center justify-center w-10 h-10 rounded-lg text-white hover:bg-white/10 transition"
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
          <div className="lg:hidden pb-4 border-t border-white/10 pt-3 space-y-1 animate-fadeIn">
            {NAV_LINKS.map((l) => (
              <Link
                key={l.to}
                to={l.to}
                className="flex items-center gap-2 px-3 py-2.5 rounded-lg text-gray-200 hover:bg-white/10 hover:text-emerald-400 transition-colors font-semibold"
              >
                <l.Icon className="w-4 h-4" /> {l.label}
              </Link>
            ))}

            {admin && (
              <a
                href={`${BACKEND_URL}/admin`}
                className="flex items-center gap-2 px-3 py-2.5 rounded-lg text-yellow-400 hover:bg-white/10 hover:text-yellow-300 transition-colors font-semibold"
              >
                <ChartBarIcon className="w-4 h-4" /> Panel Admin
              </a>
            )}

            <div className="pt-2 mt-2 border-t border-white/10">
              {authenticated ? (
                <div className="flex items-center justify-between px-3 py-2">
                  <span className="flex items-center gap-1.5 text-gray-300 text-sm">
                    <UserCircleIcon className="w-5 h-5" /> {admin ? 'Administrador' : user?.name || 'Usuario'}
                  </span>
                  <button
                    onClick={handleLogout}
                    className="px-3 py-1 rounded bg-red-500 text-white hover:bg-red-600 text-sm font-medium transition"
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