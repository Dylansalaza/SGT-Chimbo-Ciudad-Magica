import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import {
  BuildingLibraryIcon,
  MapPinIcon,
  CalendarDaysIcon,
  XMarkIcon,
} from '@heroicons/react/24/solid';

// ============================================================================
// COMPONENTE: Footer
// Pie de página global (vive en App.jsx, fuera de <Routes>, así aparece en
// todas las vistas públicas). Incluye el modal de créditos del proyecto,
// que se abre desde el enlace "Creado por..." de la barra inferior.
// ============================================================================
export default function Footer() {
  const [showCreditos, setShowCreditos] = useState(false);

  return (
    <>
      <footer className="bg-gray-900 text-white mt-8">
        {/* Franja de acento con el degradado de marca */}
        <div className="h-1 bg-gradient-to-r from-blue-500 via-purple-600 to-blue-500" />

        <div className="max-w-7xl mx-auto px-4 py-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">

          {/* Marca */}
          <div className="lg:col-span-2">
            <div className="flex items-center gap-2.5 mb-3">
              <div className="w-9 h-9 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center shrink-0">
                <BuildingLibraryIcon className="w-5 h-5 text-white" />
              </div>
              <span className="font-serif text-lg font-bold">San José de Chimbo</span>
            </div>
            <p className="text-sm text-gray-400 leading-relaxed max-w-sm">
              Sistema de Gestión Turística del cantón Chimbo, provincia de Bolívar — Ecuador.
              Historia colonial, naturaleza andina y tradición artesanal en un mismo destino.
            </p>
          </div>

          {/* Enlaces rápidos */}
          <div>
            <h3 className="text-xs font-bold uppercase tracking-wider text-gray-300 mb-3">Explorar</h3>
            <ul className="space-y-2 text-sm text-gray-400">
              <li><Link to="/" className="hover:text-blue-400 transition-colors">Inicio</Link></li>
              <li><Link to="/eventos" className="hover:text-blue-400 transition-colors">Eventos</Link></li>
              <li><Link to="/noticias" className="hover:text-blue-400 transition-colors">Noticias</Link></li>
              <li><Link to="/galerias" className="hover:text-blue-400 transition-colors">Galerías</Link></li>
              <li><Link to="/mapa" className="hover:text-blue-400 transition-colors">Mapa Turístico</Link></li>
            </ul>
          </div>

          {/* El cantón */}
          <div>
            <h3 className="text-xs font-bold uppercase tracking-wider text-gray-300 mb-3">El Cantón</h3>
            <ul className="space-y-2.5 text-sm text-gray-400">
              <li className="flex items-center gap-2"><MapPinIcon className="w-3.5 h-3.5 shrink-0 text-blue-400" /> Provincia de Bolívar, Ecuador</li>
              <li className="flex items-center gap-2"><CalendarDaysIcon className="w-3.5 h-3.5 shrink-0 text-blue-400" /> Cantonizado el 3 de marzo de 1860</li>
            </ul>
          </div>
        </div>

        <div className="border-t border-white/10 bg-black/20">
          <div className="max-w-7xl mx-auto px-4 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-500">
            <p>© 2026 Municipio de San José de Chimbo. Todos los derechos reservados.</p>
            <button type="button" onClick={() => setShowCreditos(true)} className="hover:text-blue-400 underline underline-offset-2 transition-colors">
              Creado por Dylan Salazar y Thalía Quinatoa
            </button>
          </div>
        </div>
      </footer>

      {/* Modal de créditos del proyecto */}
      {showCreditos && (
        <div className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" onClick={() => setShowCreditos(false)}>
          <div className="relative bg-white dark:bg-[#242424] rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden text-center" onClick={(e) => e.stopPropagation()}>
            {/* Franja de acento superior */}
            <div className="h-1.5 bg-gradient-to-r from-blue-500 via-purple-600 to-blue-500" />

            <div className="p-8 sm:p-10">
              <button type="button" onClick={() => setShowCreditos(false)} className="absolute top-5 right-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                <XMarkIcon className="w-5 h-5" />
              </button>

              <div className="w-14 h-14 mx-auto mb-4 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg shadow-blue-500/20">
                <BuildingLibraryIcon className="w-7 h-7 text-white" />
              </div>

              <h2 className="font-serif text-xl font-bold text-gray-800 dark:text-white">Sistema de Gestión Turística</h2>
              <span className="inline-block bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 text-[11px] font-bold uppercase tracking-widest px-3 py-1 rounded-full mt-2 mb-6">
                SGT Chimbo · v1.0
              </span>

              <div className="space-y-3 text-left bg-gray-50 dark:bg-gray-800/50 rounded-xl p-5">
                <div className="flex items-center justify-between gap-4">
                  <span className="text-xs text-gray-400 shrink-0">Desarrollado por</span>
                  <span className="text-sm font-semibold text-gray-800 dark:text-white text-right">Dylan Salazar y Thalía Quinatoa</span>
                </div>
                <div className="flex items-center justify-between gap-4 border-t border-gray-200 dark:border-gray-700 pt-3">
                  <span className="text-xs text-gray-400 shrink-0">Carrera</span>
                  <span className="text-sm font-semibold text-gray-800 dark:text-white text-right">Ingeniería en Software</span>
                </div>
                <div className="flex items-center justify-between gap-4 border-t border-gray-200 dark:border-gray-700 pt-3">
                  <span className="text-xs text-gray-400 shrink-0">Universidad</span>
                  <span className="text-sm font-semibold text-gray-800 dark:text-white text-right">Universidad Estatal de Bolívar</span>
                </div>
              </div>

              <p className="text-xs text-gray-400 mt-5">Desarrollado como trabajo de titulación.</p>
              <p className="text-xs text-gray-400 mt-1">© 2026 Dylan Salazar y Thalía Quinatoa. Todos los derechos reservados.</p>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
