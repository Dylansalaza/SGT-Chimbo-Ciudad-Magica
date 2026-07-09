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
    },
  },
  plugins: [],
}