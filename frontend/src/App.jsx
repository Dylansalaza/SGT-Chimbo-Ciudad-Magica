import React, { useEffect, useState, Suspense, lazy } from "react";
import { BrowserRouter as Router, Routes, Route, Navigate, useLocation } from "react-router-dom";

const API_URL = import.meta.env.VITE_API_URL || "http://127.0.0.1:3000/api";

// ==========================================
// 🏞️ COMPONENTES GLOBALES (carga inmediata: se ven en todas las vistas)
// ==========================================
import Navbar from "./components/Navbar";              // Header de navegación global
import Footer from "./components/Footer";             // Pie de página global

// ==========================================
// 📦 CÓDIGO DIVIDIDO POR RUTA (code-splitting con React.lazy)
// Cada página se descarga SOLO cuando el usuario la visita. Así el bundle
// inicial del Inicio ya no arrastra Leaflet (mapa), Swiper de otras vistas,
// react-dropzone, etc. → arranque mucho más rápido (mejor FCP/LCP).
// ==========================================
const Chatbot       = lazy(() => import("./components/Chatbot"));   // flotante, no crítico para el primer pintado
const Home          = lazy(() => import("./pages/Home"));
const Eventos       = lazy(() => import("./pages/Eventos"));
const EventoDetalle = lazy(() => import("./pages/EventoDetalle"));
const Galerias      = lazy(() => import("./pages/Galerias"));
const Noticias      = lazy(() => import("./pages/Noticias"));
const NoticiaDetalle= lazy(() => import("./pages/NoticiaDetalle"));
const Login         = lazy(() => import("./pages/login")); // el archivo real es 'login.jsx' (Linux distingue mayúsculas)
const MapaTurismo   = lazy(() => import("./pages/ChimboMap")); // arrastra Leaflet (~150 KB): ahora solo se carga en /mapa


// ============================================================================
// COMPONENTE: PageTransition
// Envuelve el contenido de las rutas. Al cambiar de ruta, el `key` fuerza un
// remontaje del contenedor y la clase `page-enter` (definida en index.css)
// reproduce una entrada rápida y suave (fade + leve desplazamiento, 250 ms,
// ease-out). También sube el scroll al inicio en cada navegación. Respeta
// `prefers-reduced-motion` (la animación se desactiva en index.css).
// ============================================================================
function PageTransition({ children }) {
  const location = useLocation();
  // En el PRIMER pintado no animamos la entrada: la animación `page-enter`
  // (fade + desplazamiento) parte de opacity:0, y como el elemento LCP del
  // Inicio (el héroe) vive dentro de este contenedor, esa animación retrasaba
  // el LCP ~0.4 s sin aportar nada (no hay una vista previa "desde donde"
  // transicionar en la carga inicial). La transición se activa SOLO a partir
  // del primer cambio de ruta, que es cuando sí hay un antes/después que suavizar.
  const primeraCarga = React.useRef(true);
  const animar = !primeraCarga.current;

  useEffect(() => {
    // Tras el montaje inicial, las siguientes navegaciones sí animan.
    primeraCarga.current = false;
  }, []);

  useEffect(() => {
    window.scrollTo({ top: 0, left: 0, behavior: "instant" });
  }, [location.pathname]);

  return (
    <div key={location.pathname} className={animar ? "page-enter" : undefined}>
      {children}
    </div>
  );
}

// ============================================================================
// COMPONENTE RAÍZ: App
// Define el enrutador (React Router) con todas las páginas públicas del
// sitio, envuelve todo en el Navbar fijo y el Chatbot flotante (ambos viven
// fuera de <Routes> para persistir al cambiar de página).
// ============================================================================
export default function App() {
  // Registra una visita anónima UNA sola vez por sesión del navegador (para el
  // reporte). El flag se marca ANTES del fetch a propósito: así el doble montaje
  // de React StrictMode (dev) o cualquier remontaje concurrente no dispara un
  // segundo POST (antes el flag se fijaba en el .then async y ambos pasaban el
  // guard, inflando el conteo). Si el POST falla, se limpia para reintentar.
  useEffect(() => {
    if (sessionStorage.getItem("visita_registrada")) return;
    sessionStorage.setItem("visita_registrada", "1");
    fetch(`${API_URL}/registro-visita`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ url: window.location.href }),
    }).catch(() => sessionStorage.removeItem("visita_registrada"));
  }, []);

  // Difiere el montaje del Chatbot hasta que el navegador esté OCIOSO (o el
  // usuario interactúe): así su chunk deja de competir con el render inicial
  // del Inicio, lo que baja el TBT (bloqueo del hilo principal) en móvil. La
  // burbuja de chat aparece un instante después, sin costo visible.
  const [mostrarChatbot, setMostrarChatbot] = useState(false);
  useEffect(() => {
    const activar = () => setMostrarChatbot(true);
    const opts = { once: true, passive: true };
    window.addEventListener("pointerdown", activar, opts);
    window.addEventListener("keydown", activar, opts);
    let idleId, timeoutId;
    if ("requestIdleCallback" in window) {
      idleId = window.requestIdleCallback(activar, { timeout: 3000 });
    } else {
      timeoutId = setTimeout(activar, 2000);
    }
    return () => {
      window.removeEventListener("pointerdown", activar);
      window.removeEventListener("keydown", activar);
      if (idleId != null && window.cancelIdleCallback) window.cancelIdleCallback(idleId);
      if (timeoutId != null) clearTimeout(timeoutId);
    };
  }, []);

  return (
    <Router>
      <div className="app-container min-h-screen bg-gray-50 dark:bg-[#242424] transition-colors flex flex-col justify-between">

        {/* 📋 HEADER / NAVBAR GLOBAL (visible en todas las vistas públicas) */}
        <Navbar />

        {/* ==========================================
            🔀 ENRUTADOR PRINCIPAL (RUTAS PÚBLICAS REESTRUCTURADAS)
           ========================================== */}
        {/* pt-16 deja espacio para el navbar fijo (h-16).
            min-h-screen: el área principal SIEMPRE mide al menos una pantalla,
            así el footer queda siempre por debajo del pliegue (incluso durante
            la carga). Evita que el footer, visible al fondo mientras carga,
            "salte" hacia abajo al aparecer el contenido (era la causa del CLS). */}
        <main className="flex-grow min-h-screen pt-16">
          <PageTransition>
          {/* Fallback mientras se descarga el chunk de la ruta. Reserva el alto
              COMPLETO de la ventana (min-h-screen) para que el footer quede
              siempre por debajo del pliegue: así, al aparecer el contenido real,
              el footer no "salta" hacia abajo (evita CLS, que penalizaba mucho). */}
          <Suspense fallback={<div className="min-h-screen" aria-hidden="true" />}>
          <Routes>
            {/* 🏠 Ruta de Bienvenida / Inicio */}
            <Route path="/" element={<Home />} />

            {/* 🗺️ El Mapa Interactivo de San José de Chimbo (Tu Core) */}
            <Route path="/mapa" element={<MapaTurismo />} />

            {/* 🎉 NUEVA: Sección de Eventos programados en el cantón */}
            <Route path="/eventos" element={<Eventos />} />
            <Route path="/eventos/:id" element={<EventoDetalle />} />

            {/* 🖼️ NUEVA: Galería de imágenes turísticas */}
            <Route path="/galerias" element={<Galerias />} />

            {/* 📰 Sección de Noticias locales para el turista */}
            <Route path="/noticias" element={<Noticias />} />
            <Route path="/noticias/:id" element={<NoticiaDetalle />} />

            {/* 🔐 Login público (Opcional) */}
            <Route path="/login" element={<Login />} />

            {/* 🔄 Redirección por defecto: Cualquier ruta no existente manda al Home */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
          </Suspense>
          </PageTransition>
        </main>

        {/* 🔻 PIE DE PÁGINA GLOBAL (visible en todas las vistas públicas) */}
        <Footer />

        {/* ==========================================
            🤖 ASISTENTE VIRTUAL PERMANENTE (CHATBOT)
           ========================================== */}
        {/* Fuera del enrutador (flota en todas las páginas) y diferido: su chunk
            se descarga cuando el navegador está ocioso o el usuario interactúa
            (ver `mostrarChatbot`), sin bloquear el render del Inicio. */}
        {mostrarChatbot && (
          <Suspense fallback={null}>
            <Chatbot />
          </Suspense>
        )}
      </div>
    </Router>
  );
}