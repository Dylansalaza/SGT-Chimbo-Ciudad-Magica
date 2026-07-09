// Iconos SVG que no existen en el set de Heroicons (@heroicons/react/24/solid),
// dibujados a mano en el mismo estilo "filled" para que combinen con el resto.
// Todos aceptan las mismas props que un icono de Heroicons: className, etc.

export function AccessibilityIcon({ className = 'w-5 h-5' }) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" className={className} aria-hidden="true">
      <circle cx="14.5" cy="4.5" r="1.9" />
      <path
        fillRule="evenodd"
        d="M9.5 8.5h5.6a1 1 0 0 1 .95.68l1.02 3h3.43a1 1 0 1 1 0 2h-2.76l1.1 3.24a1 1 0 1 1-1.9.63l-.5-1.48a6 6 0 1 1-7.24-7.51L9 9.53a1 1 0 0 1 .5-1.03Zm.9 3.86A4 4 0 1 0 15.6 17.6l-1.2-3.53H9.9a1 1 0 0 1-.5-.13Z"
      />
    </svg>
  );
}

export function ContrastIcon({ className = 'w-5 h-5' }) {
  return (
    <svg viewBox="0 0 24 24" className={className} aria-hidden="true">
      <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" strokeWidth="1.8" />
      <path d="M12 3a9 9 0 0 1 0 18Z" fill="currentColor" />
    </svg>
  );
}

export function UtensilsIcon({ className = 'w-5 h-5' }) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" className={className} aria-hidden="true">
      <rect x="5.2" y="2" width="2.2" height="20" rx="1.1" transform="rotate(20 6.3 12)" />
      <rect x="16.6" y="2" width="2.2" height="20" rx="1.1" transform="rotate(-20 17.7 12)" />
    </svg>
  );
}

export function TargetIcon({ className = 'w-5 h-5' }) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" className={className} aria-hidden="true">
      <path fillRule="evenodd" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 4a6 6 0 1 1 0 12 6 6 0 0 1 0-12Z" />
      <circle cx="12" cy="12" r="2.2" />
    </svg>
  );
}

export function StopIcon({ className = 'w-5 h-5' }) {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" className={className} aria-hidden="true">
      <rect x="6" y="6" width="12" height="12" rx="2" />
    </svg>
  );
}
