import React, { useEffect, useState } from 'react';
import { ArrowPathIcon, MoonIcon } from '@heroicons/react/24/solid';
import { AccessibilityIcon } from './icons/CustomIcons';

// Escalas de tamaño de letra (se aplican al <html>, así escalan todos los textos en rem)
const ESCALAS = [90, 100, 112, 125, 140]; // %
const BASE_INDEX = 1; // 100%

// ============================================================================
// COMPONENTE: AccessibilityWidget
// NOTA: actualmente no está montado en ningún lugar de la app (se quitó del
// Navbar a pedido del usuario), pero se deja el código listo por si se quiere
// reactivar más adelante. Controla únicamente el tamaño del texto (el alto
// contraste dejó de ser manual: ahora se activa solo en modo oscuro, ver
// index.css). Botón ♿ que despliega un panel con controles A−/A+ .
// ============================================================================
export default function AccessibilityWidget() {
  const [abierto, setAbierto] = useState(false); // Si el panel desplegable está visible
  const [idx, setIdx] = useState(BASE_INDEX);    // Índice actual dentro de ESCALAS

  // Cargar preferencia guardada (persiste entre visitas)
  useEffect(() => {
    const savedIdx = parseInt(localStorage.getItem('a11y_escala'));
    if (!isNaN(savedIdx)) setIdx(savedIdx);
  }, []);

  // Aplicar el tamaño de letra al <html> cada vez que cambia el índice,
  // así todo el sitio escala (rem) en proporción.
  useEffect(() => {
    document.documentElement.style.fontSize = ESCALAS[idx] + '%';
    localStorage.setItem('a11y_escala', String(idx));
  }, [idx]);

  const subir    = () => setIdx(i => Math.min(i + 1, ESCALAS.length - 1)); // Aumenta un paso (tope: escala más grande)
  const bajar    = () => setIdx(i => Math.max(i - 1, 0));                  // Reduce un paso (tope: escala más chica)
  const resetear = () => setIdx(BASE_INDEX);                              // Vuelve al 100%

  return (
    <div className="relative">
      <button
        onClick={() => setAbierto(o => !o)}
        aria-label="Abrir opciones de accesibilidad"
        title="Accesibilidad"
        className="flex items-center justify-center w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors shrink-0"
      >
        <AccessibilityIcon className="w-5 h-5" />
      </button>

      {abierto && (
        <div className="absolute right-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-gray-200 p-4 w-60 animate-fadeIn z-[60]" role="dialog" aria-label="Opciones de accesibilidad">
          <p className="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2"><AccessibilityIcon className="w-4 h-4" /> Accesibilidad</p>

          <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tamaño del texto</p>
          <div className="flex items-center gap-2 mb-1">
            <button onClick={bajar} aria-label="Reducir tamaño de letra"
              className="w-9 h-9 rounded-lg border border-gray-300 text-gray-700 font-bold hover:bg-gray-100">A−</button>
            <span className="flex-1 text-center text-sm font-bold text-gray-700">{ESCALAS[idx]}%</span>
            <button onClick={subir} aria-label="Aumentar tamaño de letra"
              className="w-9 h-9 rounded-lg border border-gray-300 text-gray-700 font-bold hover:bg-gray-100 text-lg">A+</button>
          </div>

          <p className="text-[11px] text-gray-400 mt-2 mb-3 flex items-center gap-1"><MoonIcon className="w-3 h-3 shrink-0" /> El alto contraste se activa automáticamente en modo oscuro.</p>

          <button onClick={resetear} className="w-full px-3 py-2 rounded-lg text-xs font-semibold text-gray-500 hover:bg-gray-100 flex items-center justify-center gap-1.5">
            <ArrowPathIcon className="w-3.5 h-3.5" /> Restablecer
          </button>
        </div>
      )}
    </div>
  );
}
