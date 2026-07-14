import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App'
import './index.css'

// El sitio SIEMPRE debe abrir arriba del todo (en el inicio). Por defecto el
// navegador "recuerda" la posición de scroll anterior y la restaura al reabrir
// o recargar el enlace, dejando la página a media altura. En 'manual' esa
// restauración se desactiva y el control del scroll queda en la app (que ya
// sube al inicio en cada navegación desde PageTransition en App.jsx).
if ('scrollRestoration' in window.history) {
  window.history.scrollRestoration = 'manual'
}

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
)