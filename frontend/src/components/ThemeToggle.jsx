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
  // Estado inicial: el sitio SIEMPRE abre en modo CLARO por defecto. Solo se
  // muestra en oscuro si el propio usuario lo activó antes con este botón (queda
  // guardado en localStorage). Ya NO se sigue la preferencia del sistema
  // operativo: así, al abrir el enlace en un equipo/celular en modo oscuro, el
  // sitio igual carga en claro como se espera.
  const [dark, setDark] = useState(() => localStorage.getItem('theme') === 'dark');

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