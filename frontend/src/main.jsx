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

// La sesión del sitio se liga a la PESTAÑA (vive en sessionStorage, que el
// navegador borra al cerrar la pestaña). Si quedó una sesión "recordada" de
// antes en localStorage —que persistía tras cerrar la pestaña—, se migra una
// sola vez a sessionStorage y se limpia localStorage: así la sesión actual
// sigue válida en ESTA pestaña, pero ya se cerrará al cerrarla, sin obligar a
// pulsar "Cerrar sesión".
try {
  ['token', 'user'].forEach((clave) => {
    const valor = localStorage.getItem(clave)
    if (valor !== null) {
      if (sessionStorage.getItem(clave) === null) sessionStorage.setItem(clave, valor)
      localStorage.removeItem(clave)
    }
  })
} catch (e) { /* almacenamiento no disponible: se ignora */ }

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
)