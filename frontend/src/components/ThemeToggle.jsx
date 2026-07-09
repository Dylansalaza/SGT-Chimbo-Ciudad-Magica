import { useEffect, useState } from 'react';
import { SunIcon, MoonIcon } from '@heroicons/react/24/solid';

// ============================================================================
// COMPONENTE: ThemeToggle
// Botón que alterna entre modo claro y oscuro para toda la aplicación,
// agregando/quitando la clase "dark" en <html> (así funcionan las clases
// dark: de Tailwind en cualquier componente). El alto contraste automático
// (ver index.css) también depende de esta clase "dark".
// ============================================================================
export default function ThemeToggle() {
  // Estado inicial: usa la preferencia guardada en localStorage si existe;
  // si es la primera visita, respeta la preferencia del sistema operativo.
  const [dark, setDark] = useState(() => {
    const saved = localStorage.getItem('theme');
    if (saved) return saved === 'dark';
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
  });

  // Cada vez que cambia "dark", sincroniza la clase del <html> y guarda la
  // preferencia para que persista entre visitas (recarga de página, etc.)
  useEffect(() => {
    const root = document.documentElement;
    if (dark) {
      root.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    } else {
      root.classList.remove('dark');
      localStorage.setItem('theme', 'light');
    }
  }, [dark]);

  return (
    <button
      onClick={() => setDark(!dark)}
      aria-label={dark ? 'Activar modo claro' : 'Activar modo oscuro'}
      title={dark ? 'Modo claro' : 'Modo oscuro'}
      className="flex items-center justify-center w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors shrink-0"
    >
      {dark ? <SunIcon className="w-5 h-5" /> : <MoonIcon className="w-5 h-5" />}
    </button>
  );
}