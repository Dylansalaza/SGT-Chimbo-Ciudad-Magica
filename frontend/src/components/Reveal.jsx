import { useEffect, useRef } from 'react';

// ============================================================================
// COMPONENTE: Reveal
// Envuelve cualquier contenido y lo hace aparecer (fade + leve subida) la
// primera vez que entra al viewport. La animación vive en index.css (`.reveal`
// + `.is-visible`); aquí solo se observa el viewport y se añade la clase.
// `delay` (ms) permite escalonar varios Reveal contiguos.
//
// Robustez: threshold 0 + rootMargin negativo abajo para disparar de forma
// fiable al hacer scroll, y una red de seguridad (setTimeout) que muestra el
// contenido si el observer no llega a dispararse (nunca queda invisible).
// ============================================================================
export default function Reveal({ as: Tag = 'div', delay = 0, className = '', children, ...rest }) {
  const ref = useRef(null);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;

    const mostrar = () => el.classList.add('is-visible');

    const obs = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          mostrar();
          obs.disconnect();
          clearTimeout(seguro);
        }
      },
      { threshold: 0, rootMargin: '0px 0px -8% 0px' }
    );
    obs.observe(el);

    const seguro = setTimeout(() => { mostrar(); obs.disconnect(); }, 1600);

    return () => { obs.disconnect(); clearTimeout(seguro); };
  }, []);

  return (
    <Tag ref={ref} style={{ transitionDelay: `${delay}ms`, ...(rest.style || {}) }} className={`reveal ${className}`} {...rest}>
      {children}
    </Tag>
  );
}
