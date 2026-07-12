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
      // ── Paleta institucional del cantón: VERDE + DORADO ──
      // Un único acento principal (verde) y uno secundario (oro), tomados de
      // los colores cantonales. El verde se reformuló hacia un tono bosque
      // más FRESCO y vivo que antes (un punto más de saturación y luz), para
      // que se sienta vital sin perder la sobriedad de un sitio municipal.
      // Todos los `green-*` del sitio (botones, enlaces, badges) usan esta
      // paleta; el `gold-*` reemplaza al amarillo/azul disperso de antes.
      colors: {
        green: {
          50:  '#e8f9ee',
          100: '#c6f0d4',
          200: '#90e2ae',
          300: '#4fce82',
          400: '#18b25b',
          500: '#059c45',
          600: '#00913f',   // ← verde del logo de la Alcaldía de Chimbo
          700: '#04752f',
          800: '#095c28',
          900: '#0b4b24',
          950: '#022c13',
        },
        gold: {
          50:  '#fdfaec',
          100: '#faf1c8',
          200: '#f5e08d',
          300: '#efca52',
          400: '#eab52a',
          500: '#d99a16',
          600: '#bd7510',
          700: '#975211',
          800: '#7c4115',
          900: '#693717',
          950: '#3d1c08',
        },
      },
      // Sombras teñidas con el verde institucional (en vez del negro plano),
      // para que la elevación de tarjetas y botones se sienta parte del tono
      // general del sitio (recomendación de la skill de rediseño).
      boxShadow: {
        'green-sm': '0 1px 2px 0 rgba(0, 145, 63, 0.08)',
        'green-md': '0 6px 20px -6px rgba(0, 145, 63, 0.22)',
        'green-lg': '0 18px 40px -12px rgba(0, 145, 63, 0.30)',
        'gold-glow': '0 8px 30px -8px rgba(217, 154, 22, 0.45)',
      },
      // Curvas de easing fuertes (filosofía de Emil Kowalski): las curvas por
      // defecto de CSS son "débiles"; estas dan el punch que hace que las
      // animaciones se sientan intencionales. Uso: `ease-out-strong`, etc.
      transitionTimingFunction: {
        'out-strong': 'cubic-bezier(0.23, 1, 0.32, 1)',
        'in-out-strong': 'cubic-bezier(0.77, 0, 0.175, 1)',
        'drawer': 'cubic-bezier(0.32, 0.72, 0, 1)',
      },
    },
  },
  plugins: [],
}
