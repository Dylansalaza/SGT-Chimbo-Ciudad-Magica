import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  BuildingLibraryIcon,
  MapPinIcon,
  CalendarDaysIcon,
  XMarkIcon,
  EyeIcon,
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
      <footer className="bg-green-950 text-white mt-8">
        {/* Franja de acento animada que une los colores del cantón (verde→oro) */}
        <div className="h-1.5 brand-gradient-animated" />

        <div className="max-w-7xl mx-auto px-4 py-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">

          {/* Marca */}
          <div className="lg:col-span-2">
            <div className="flex items-center gap-2.5 mb-3">
              <span className="grid place-items-center w-9 h-9 rounded-xl brand-gradient-bg ring-1 ring-inset ring-gold-400/50 text-white font-serif font-black text-base leading-none">
                C
              </span>
              <div className="flex flex-col leading-none">
                <span className="font-extrabold italic text-white text-xl tracking-tight">SGT</span>
                <span className="font-extrabold text-gold-400 text-sm tracking-wide leading-tight">CHIMBO</span>
                <span className="text-[8px] font-semibold tracking-[0.3em] text-green-200/70 mt-1">GESTIÓN TURÍSTICA</span>
              </div>
            </div>
            <p className="text-sm text-green-100/60 leading-relaxed max-w-sm">
              Sistema de Gestión Turística del cantón Chimbo, provincia de Bolívar, Ecuador.
              Historia colonial, naturaleza andina y tradición artesanal en un mismo destino.
            </p>
          </div>

          {/* Enlaces rápidos */}
          <div>
            <h3 className="text-xs font-bold uppercase tracking-wider text-green-200/80 mb-3">Explorar</h3>
            <ul className="space-y-2 text-sm text-green-100/60">
              <li><Link to="/" className="hover:text-gold-300 transition-colors">Inicio</Link></li>
              <li><Link to="/eventos" className="hover:text-gold-300 transition-colors">Eventos</Link></li>
              <li><Link to="/noticias" className="hover:text-gold-300 transition-colors">Noticias</Link></li>
              <li><Link to="/galerias" className="hover:text-gold-300 transition-colors">Galerías</Link></li>
              <li><Link to="/mapa" className="hover:text-gold-300 transition-colors">Mapa Turístico</Link></li>
            </ul>
          </div>

          {/* El cantón */}
          <div>
            <h3 className="text-xs font-bold uppercase tracking-wider text-green-200/80 mb-3">El Cantón</h3>
            <ul className="space-y-2.5 text-sm text-green-100/60">
              <li className="flex items-center gap-2"><MapPinIcon className="w-3.5 h-3.5 shrink-0 text-gold-400" /> Provincia de Bolívar, Ecuador</li>
              <li className="flex items-center gap-2"><CalendarDaysIcon className="w-3.5 h-3.5 shrink-0 text-gold-400" /> Cantonizado el 3 de marzo de 1860</li>
              {visitas !== null && (
                <li className="flex items-center gap-2">
                  <EyeIcon className="w-3.5 h-3.5 shrink-0 text-gold-400" />
                  <span className="tabular-nums text-green-50">{Number(visitas).toLocaleString('es-ES')}</span> visitas al portal
                </li>
              )}
            </ul>
          </div>
        </div>

        <div className="border-t border-white/10 bg-black/20">
          <div className="max-w-7xl mx-auto px-4 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-green-100/50">
            <p>© 2026 Municipio de San José de Chimbo. Todos los derechos reservados.</p>
            <button type="button" onClick={() => setShowCreditos(true)} className="hover:text-gold-300 underline underline-offset-2 transition-colors">
              Creado por Pablo Salazar y Mayra Quinatoa
            </button>
          </div>
        </div>
      </footer>

      {/* Modal de créditos — formato horizontal, paleta de marca verde+oro */}
      {showCreditos && (
        <div
          className="fixed inset-0 bg-green-950/70 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-modal-backdrop"
          onClick={() => setShowCreditos(false)}
        >
          <div
            className="relative bg-white rounded-2xl shadow-green-lg w-full max-w-3xl overflow-hidden animate-modal-pop flex flex-col sm:flex-row ring-1 ring-black/5"
            onClick={(e) => e.stopPropagation()}
          >
            <button
              type="button"
              onClick={() => setShowCreditos(false)}
              aria-label="Cerrar"
              className="absolute top-3.5 right-3.5 w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center transition-colors duration-150 active:scale-95 z-10"
            >
              <XMarkIcon className="w-4 h-4" />
            </button>

            {/* ── Panel de marca (izquierda) ── */}
            <aside className="relative sm:w-[42%] shrink-0 px-8 py-9 flex flex-col justify-center overflow-hidden bg-gradient-to-br from-green-950 via-green-900 to-green-700">
              {/* Resplandores del mismo tono para dar profundidad */}
              <div className="absolute -top-12 -right-10 w-40 h-40 rounded-full bg-green-500/15 blur-2xl pointer-events-none" />
              <div className="absolute -bottom-16 -left-12 w-44 h-44 rounded-full bg-green-950/40 blur-2xl pointer-events-none" />

              <div className="relative w-14 h-14 mb-5 mx-auto rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center ring-1 ring-inset ring-green-300/40">
                <BuildingLibraryIcon className="w-7 h-7 text-white" />
              </div>

              <h2 className="relative font-serif text-2xl font-bold text-white leading-tight tracking-tight text-balance">
                Sistema de Gestión Turística
              </h2>

              <span className="relative inline-flex self-start items-center gap-1.5 mt-4 bg-green-950/40 text-green-100 ring-1 ring-inset ring-green-300/30 text-[11px] font-bold uppercase tracking-[0.18em] px-3 py-1 rounded-full">
                SGT Chimbo · v1.0
              </span>

              <p className="relative mt-5 text-[13px] leading-relaxed text-green-50/80 max-w-[24ch]">
                Portal turístico del cantón Chimbo, provincia de Bolívar.
              </p>
            </aside>

            {/* ── Ficha técnica (derecha) ── */}
            <div className="flex-1 min-w-0 flex flex-col">
              <dl className="px-8 pt-9 pb-2 divide-y divide-green-100">
                <div className="py-4 animate-fade-in-up" style={{ animationDelay: '60ms' }}>
                  <dt className="text-[10px] font-semibold uppercase tracking-[0.15em] text-green-700 mb-2">Desarrollado por</dt>
                  <dd className="space-y-1.5">
                    <span className="flex items-center gap-2.5 text-sm font-semibold text-green-900 leading-snug">
                      <span className="w-1.5 h-1.5 rounded-full bg-green-600 ring-2 ring-green-600/20 shrink-0" />
                      Pablo Dylan Salazar Bonilla
                    </span>
                    <span className="flex items-center gap-2.5 text-sm font-semibold text-green-900 leading-snug">
                      <span className="w-1.5 h-1.5 rounded-full bg-green-600 ring-2 ring-green-600/20 shrink-0" />
                      Mayra Thalía Quinatoa Caizaguano
                    </span>
                  </dd>
                </div>
                <div className="py-4 animate-fade-in-up" style={{ animationDelay: '130ms' }}>
                  <dt className="text-[10px] font-semibold uppercase tracking-[0.15em] text-green-700 mb-1">Carrera</dt>
                  <dd className="text-sm font-semibold text-green-900 leading-snug">Ingeniería en Software</dd>
                </div>
                <div className="py-4 animate-fade-in-up" style={{ animationDelay: '200ms' }}>
                  <dt className="text-[10px] font-semibold uppercase tracking-[0.15em] text-green-700 mb-1">Universidad</dt>
                  <dd className="text-sm font-semibold text-green-900 leading-snug">Universidad Estatal de Bolívar</dd>
                </div>
              </dl>

              <div className="mt-auto px-8 py-5 border-t border-green-100 bg-green-50/50">
                <p className="text-xs text-green-800/60">Trabajo de titulación · Ingeniería en Software</p>
                <p className="text-xs text-green-800/60 mt-0.5">© 2026 Pablo Salazar y Mayra Quinatoa. Todos los derechos reservados.</p>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
