import { useEffect, useState } from 'react';
import { ContrastIcon } from './icons/CustomIcons';

// Mismo CSS y misma llave de localStorage que usa AccessibilityWidget.jsx,
// así este botón rápido de la barra de navegación y el panel de accesibilidad
// (♿, esquina inferior izquierda) controlan exactamente el mismo modo.
const CSS_CONTRASTE = `
  html.a11y-contraste { filter: contrast(1.15) saturate(1.15); }
  html.a11y-contraste a { text-decoration: underline; }
  html.a11y-contraste body { background: #ffffff; }
  html.a11y-contraste *:focus { outline: 3px solid #1d4ed8 !important; outline-offset: 2px; }
`;

function asegurarEstilo() {
  if (!document.getElementById('a11y-style')) {
    const style = document.createElement('style');
    style.id = 'a11y-style';
    style.textContent = CSS_CONTRASTE;
    document.head.appendChild(style);
  }
}

export default function HighContrastToggle() {
  const [contraste, setContraste] = useState(() => localStorage.getItem('a11y_contraste') === '1');

  // Se asegura de que el <style> exista y se mantiene sincronizado si el
  // alto contraste se activa/desactiva desde el widget de accesibilidad.
  useEffect(() => {
    asegurarEstilo();
    const sync = () => setContraste(localStorage.getItem('a11y_contraste') === '1');
    window.addEventListener('a11y-contraste-cambio', sync);
    return () => window.removeEventListener('a11y-contraste-cambio', sync);
  }, []);

  useEffect(() => {
    document.documentElement.classList.toggle('a11y-contraste', contraste);
    localStorage.setItem('a11y_contraste', contraste ? '1' : '0');
  }, [contraste]);

  const toggle = () => {
    setContraste((c) => !c);
    // Avisa a otros componentes (p. ej. el panel ♿) que el estado cambió.
    setTimeout(() => window.dispatchEvent(new Event('a11y-contraste-cambio')), 0);
  };

  return (
    <button
      onClick={toggle}
      aria-label={contraste ? 'Desactivar alto contraste' : 'Activar alto contraste'}
      title={contraste ? 'Alto contraste (activado)' : 'Alto contraste'}
      className={`flex items-center justify-center w-10 h-10 rounded-full transition-colors shrink-0 ${
        contraste ? 'bg-yellow-400 text-black hover:bg-yellow-300' : 'bg-white/10 hover:bg-white/20 text-white'
      }`}
    >
      <ContrastIcon className="w-5 h-5" />
    </button>
  );
}
