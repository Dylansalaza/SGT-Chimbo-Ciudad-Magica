import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    port: Number(process.env.PORT) || 5173,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:3000',
        changeOrigin: true,
      }
    }
  },
  // Mismo proxy al previsualizar el build de producción (`vite preview`), para
  // medir/probar con la API real igual que en desarrollo.
  preview: {
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:3000',
        changeOrigin: true,
      }
    }
  },
  build: {
    rollupOptions: {
      output: {
        // Separa las librerías grandes en chunks propios para que el navegador
        // los cachee entre despliegues y no engorden el chunk de cada página.
        // Leaflet queda aislado (solo lo carga /mapa vía code-splitting).
        manualChunks: {
          'react-vendor': ['react', 'react-dom', 'react-router-dom'],
          'leaflet': ['leaflet', 'react-leaflet'],
          'swiper': ['swiper', 'swiper/react'],
        },
      },
    },
  },
})