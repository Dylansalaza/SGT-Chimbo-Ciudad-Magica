import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  BuildingLibraryIcon,
  MapPinIcon,
  CalendarDaysIcon,
  XMarkIcon,
  EyeIcon,
  UserGroupIcon,
  AcademicCapIcon,
} from '@heroicons/react/24/solid';

const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:3000/api';

// ============================================================================
// COMPONENTE: Footer
// Pie de página global (vive en App.jsx, fuera de <Routes>, así aparece en
// todas las vistas públicas). Incluye el modal de créditos del proyecto,
// que se abre desde el enlace "Creado por..." de la barra inferior.
// ============================================================================
export default function Footer() {
  const [showCreditos, setShowCreditos] = useState(false);
  // Total histórico de visitas al portal (viene del endpoint público /stats).
  // Si el backend no responde, simplemente no se muestra el contador.
  const [visitas, setVisitas] = useState(null);

  useEffect(() => {
    fetch(`${API_URL}/stats`)
      .then((r) => r.json())
      .then((d) => setVisitas(d?.totales?.historico ?? null))
      .catch(() => {});
  }, []);

  return (
    <>
      <footer className="bg-gray-900 text-white mt-8">
        {/* Franja de acento con los colores institucionales del cantón */}
        <div className="h-1 bg-green-700" />
        <div className="h-0.5 bg-yellow-400" />

        <div className="max-w-7xl mx-auto px-4 py-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">

          {/* Marca */}
          <div className="lg:col-span-2">
            <div className="flex items-center mb-3">
              <div className="flex flex-col leading-none">
                <span className="font-extrabold italic text-white text-xl tracking-tight">SGT</span>
                <span className="font-extrabold text-rose-600 text-sm tracking-wide leading-tight">CHIMBO</span>
                <span className="text-[8px] font-semibold tracking-[0.3em] text-slate-300 mt-1">GESTIÓN TURÍSTICA</span>
              </div>
            </div>
            <p className="text-sm text-gray-400 leading-relaxed max-w-sm">
              Sistema de Gestión Turística del cantón Chimbo, provincia de Bolívar, Ecuador.
              Historia colonial, naturaleza andina y tradición artesanal en un mismo destino.
            </p>
          </div>

          {/* Enlaces rápidos */}
          <div>
            <h3 className="text-xs font-bold uppercase tracking-wider text-gray-300 mb-3">Explorar</h3>
            <ul className="space-y-2 text-sm text-gray-400">
              <li><Link to="/" className="hover:text-yellow-400 transition-colors">Inicio</Link></li>
              <li><Link to="/eventos" className="hover:text-yellow-400 transition-colors">Eventos</Link></li>
              <li><Link to="/noticias" className="hover:text-yellow-400 transition-colors">Noticias</Link></li>
              <li><Link to="/galerias" className="hover:text-yellow-400 transition-colors">Galerías</Link></li>
              <li><Link to="/mapa" className="hover:text-yellow-400 transition-colors">Mapa Turístico</Link></li>
            </ul>
          </div>

          {/* El cantón */}
          <div>
            <h3 className="text-xs font-bold uppercase tracking-wider text-gray-300 mb-3">El Cantón</h3>
            <ul className="space-y-2.5 text-sm text-gray-400">
              <li className="flex items-center gap-2"><MapPinIcon className="w-3.5 h-3.5 shrink-0 text-green-400" /> Provincia de Bolívar, Ecuador</li>
              <li className="flex items-center gap-2"><CalendarDaysIcon className="w-3.5 h-3.5 shrink-0 text-green-400" /> Cantonizado el 3 de marzo de 1860</li>
              {visitas !== null && (
                <li className="flex items-center gap-2">
                  <EyeIcon className="w-3.5 h-3.5 shrink-0 text-green-400" />
                  <span className="tabular-nums">{Number(visitas).toLocaleString('es-ES')}</span> visitas al portal
                </li>
              )}
            </ul>
          </div>
        </div>

        <div className="border-t border-white/10 bg-black/20">
          <div className="max-w-7xl mx-auto px-4 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-500">
            <p>© 2026 Municipio de San José de Chimbo. Todos los derechos reservados.</p>
            <button type="button" onClick={() => setShowCreditos(true)} className="hover:text-yellow-400 underline underline-offset-2 transition-colors">
              Creado por Dylan Salazar y Thalía Quinatoa
            </button>
          </div>
        </div>
      </footer>

      {/* Modal de créditos del proyecto */}
      {showCreditos && (
        <div
          className="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-modal-backdrop"
          onClick={() => setShowCreditos(false)}
        >
          <div
            className="relative bg-white dark:bg-[#1c1c1c] rounded-2xl shadow-2xl shadow-black/40 w-full max-w-md overflow-hidden animate-modal-pop"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Barra de acento superior con brillo azul en movimiento */}
            <div className="animated-accent-bar h-1.5" />

            {/* ── Cabecera con degradado e ícono institucional ── */}
            <div className="animated-gradient relative px-8 pt-8 pb-14 text-center overflow-hidden">
              {/* Textura sutil: resplandores difusos (uno verde, uno dorado) para dar profundidad */}
              <div className="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-[#F2C230]/25 blur-2xl pointer-events-none" />
              <div className="absolute -bottom-16 -left-10 w-40 h-40 rounded-full bg-black/10 blur-2xl pointer-events-none" />

              <button
                type="button"
                onClick={() => setShowCreditos(false)}
                aria-label="Cerrar"
                className="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 active:scale-95 text-white flex items-center justify-center transition-all"
              >
                <XMarkIcon className="w-4 h-4" />
              </button>

              <div className="relative w-16 h-16 mx-auto mb-4 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center ring-1 ring-inset ring-[#F2C230]/50 shadow-lg">
                <BuildingLibraryIcon className="w-8 h-8 text-[#F2C230]" />
              </div>

              <h2 className="relative font-serif text-2xl font-bold text-white leading-tight">Sistema de Gestión Turística</h2>
              <span className="relative inline-block bg-[#F2C230] text-[#00294d] text-[11px] font-black uppercase tracking-widest px-3.5 py-1 rounded-full mt-3 shadow-md shadow-black/20">
                SGT Chimbo · v1.0
              </span>
            </div>

            {/* ── Ficha técnica (superpuesta a la cabecera, como una tarjeta flotante) ── */}
            <div className="relative px-6 -mt-8 pb-6">
              <div className="bg-white dark:bg-[#242424] rounded-xl shadow-lg shadow-black/10 border border-black/5 dark:border-white/10 divide-y divide-gray-100 dark:divide-gray-700">
                <div className="flex items-center gap-3 px-4 py-3.5">
                  <div className="w-8 h-8 shrink-0 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <UserGroupIcon className="w-4 h-4 text-blue-700 dark:text-blue-400" />
                  </div>
                  <div className="min-w-0">
                    <p className="text-[10px] uppercase tracking-wide text-gray-400 leading-none mb-1">Desarrollado por</p>
                    <p className="text-sm font-semibold text-gray-800 dark:text-white leading-snug truncate">Dylan Salazar y Thalía Quinatoa</p>
                  </div>
                </div>
                <div className="flex items-center gap-3 px-4 py-3.5">
                  <div className="w-8 h-8 shrink-0 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                    <AcademicCapIcon className="w-4 h-4 text-amber-500 dark:text-amber-400" />
                  </div>
                  <div className="min-w-0">
                    <p className="text-[10px] uppercase tracking-wide text-gray-400 leading-none mb-1">Carrera</p>
                    <p className="text-sm font-semibold text-gray-800 dark:text-white leading-snug truncate">Ingeniería en Software</p>
                  </div>
                </div>
                <div className="flex items-center gap-3 px-4 py-3.5">
                  <div className="w-8 h-8 shrink-0 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <BuildingLibraryIcon className="w-4 h-4 text-blue-700 dark:text-blue-400" />
                  </div>
                  <div className="min-w-0">
                    <p className="text-[10px] uppercase tracking-wide text-gray-400 leading-none mb-1">Universidad</p>
                    <p className="text-sm font-semibold text-gray-800 dark:text-white leading-snug truncate">Universidad Estatal de Bolívar</p>
                  </div>
                </div>
              </div>

              <div className="text-center mt-5 pt-4 border-t border-gray-100 dark:border-gray-800">
                <p className="text-xs text-gray-400">Desarrollado como trabajo de titulación.</p>
                <p className="text-xs text-gray-400 mt-0.5">© 2026 Dylan Salazar y Thalía Quinatoa. Todos los derechos reservados.</p>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
