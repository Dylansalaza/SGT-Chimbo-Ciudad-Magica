/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      // Manrope como fuente por defecto en TODO el sitio (texto, botones,
      // formularios). Playfair Display queda disponible como `font-serif`
      // para titulares grandes y las páginas con estética de periódico
      // (Eventos, Noticias, Galerías).
      fontFamily: {
        sans: ['Manrope', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
        serif: ['"Playfair Display"', 'Georgia', 'serif'],
      },
      // Verde institucional reformulado: un tono bosque/esmeralda más
      // apagado y sobrio (menos saturado y algo más profundo que el verde
      // por defecto de Tailwind), para un aspecto más profesional. Todos los
      // `green-*` del sitio (botones, enlaces, badges) usan esta paleta.
      colors: {
        green: {
          50:  '#eff6f2',
          100: '#d7ebe0',
          200: '#b2d6c3',
          300: '#84ba9f',
          400: '#56977b',
          500: '#3d7f63',
          600: '#347258',
          700: '#2b5f48',
          800: '#244d3b',
          900: '#1e3f31',
          950: '#0f231b',
        },
      },
    },
  },
  plugins: [],
}