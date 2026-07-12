import { useEffect, useRef } from 'react';

// ============================================================================
// COMPONENTE: StaggerGrid
// Contenedor que hace entrar a sus hijos directos en cascada (fade + leve
// subida escalonada) la primera vez que aparece en pantalla. La animación en
// sí vive en index.css (`.stagger-in` + `.is-visible`); aquí solo se observa
// el viewport con IntersectionObserver y se añade la clase `is-visible`.
//
// Robustez:
//  · threshold 0 + rootMargin negativo abajo → dispara de forma fiable en
//    cuanto el bloque asoma al hacer scroll (incluso en columnas muy altas,
//    donde un threshold alto podría no cumplirse nunca).
//  · Red de seguridad (setTimeout): si el observer no llega a dispararse por
//    lo que sea, igual mostramos el contenido para que NUNCA quede invisible.
// Uso: <StaggerGrid className="grid md:grid-cols-3 gap-6">…tarjetas…</StaggerGrid>
// ============================================================================
export default function StaggerGrid({ as: Tag = 'div', className = '', repeat = false, children, ...rest }) {
  const ref = useRef(null);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;

    // Modo REPEAT (usado solo en el Home): la cascada se reproduce cada vez
    // que el bloque entra al viewport. Alternamos `is-visible` según la
    // intersección; al quitarla y volver a ponerla, la animación reinicia.
    if (repeat) {
      const obs = new IntersectionObserver(
        ([entry]) => el.classList.toggle('is-visible', entry.isIntersecting),
        { threshold: 0, rootMargin: '0px 0px -12% 0px' }
      );
      obs.observe(el);
      return () => obs.disconnect();
    }

    // Modo por defecto (una sola vez): entra en cascada la primera vez y se queda.
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

    // Seguro anti-invisible: pase lo que pase, a los 1.6 s se muestra.
    const seguro = setTimeout(() => { mostrar(); obs.disconnect(); }, 1600);

    return () => { obs.disconnect(); clearTimeout(seguro); };
  }, [repeat]);

  return (
    <Tag ref={ref} className={`stagger-in ${className}`} {...rest}>
      {children}
    </Tag>
  );
}
