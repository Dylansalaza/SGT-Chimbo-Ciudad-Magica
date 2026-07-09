import React, { useEffect } from "react";
import { BrowserRouter as Router, Routes, Route, Navigate } from "react-router-dom";

const API_URL = import.meta.env.VITE_API_URL || "http://127.0.0.1:3000/api";

// ==========================================
// 🏞️ IMPORTACIÓN DE COMPONENTES Y PÁGINAS PÚBLICAS
// ==========================================
      // Tu mapa modularizado
import Navbar from "./components/Navbar";              // Header de navegación global
import Footer from "./components/Footer";             // Pie de página global
import Chatbot from "./components/Chatbot";           // Chatbot de asistencia turística

// Páginas importadas correctamente para el uso de los turistas
import Home from "./pages/Home";
import Eventos from "./pages/Eventos";  
import Galerias from "./pages/Galerias";                    
import Noticias from "./pages/Noticias";              
import Login from "./pages/login"; // el archivo real es 'login.jsx' (Linux distingue mayúsculas)
import MapaTurismo from "./pages/ChimboMap";


// ============================================================================
// COMPONENTE RAÍZ: App
// Define el enrutador (React Router) con todas las páginas públicas del
// sitio, envuelve todo en el Navbar fijo y el Chatbot flotante (ambos viven
// fuera de <Routes> para persistir al cambiar de página).
// ============================================================================
export default function App() {
  // Registra una visita anónima una vez por sesión del navegador (para el reporte).
  useEffect(() => {
    if (sessionStorage.getItem("visita_registrada")) return;
    fetch(`${API_URL}/registro-visita`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ url: window.location.href }),
    }).then(() => sessionStorage.setItem("visita_registrada", "1")).catch(() => {});
  }, []);

  return (
    <Router>
      <div className="app-container min-h-screen bg-gray-50 dark:bg-[#242424] transition-colors flex flex-col justify-between">

        {/* 📋 HEADER / NAVBAR GLOBAL (visible en todas las vistas públicas) */}
        <Navbar />

        {/* ==========================================
            🔀 ENRUTADOR PRINCIPAL (RUTAS PÚBLICAS REESTRUCTURADAS)
           ========================================== */}
        {/* pt-16 deja espacio para el navbar fijo (h-16) */}
        <main className="flex-grow pt-16">
          <Routes>
            {/* 🏠 Ruta de Bienvenida / Inicio */}
            <Route path="/" element={<Home />} />

            {/* 🗺️ El Mapa Interactivo de San José de Chimbo (Tu Core) */}
            <Route path="/mapa" element={<MapaTurismo />} />

            {/* 🎉 NUEVA: Sección de Eventos programados en el cantón */}
            <Route path="/eventos" element={<Eventos />} />

            {/* 🖼️ NUEVA: Galería de imágenes turísticas */}
            <Route path="/galerias" element={<Galerias />} />

            {/* 📰 Sección de Noticias locales para el turista */}
            <Route path="/noticias" element={<Noticias />} />

            {/* 🔐 Login público (Opcional) */}
            <Route path="/login" element={<Login />} />

            {/* 🔄 Redirección por defecto: Cualquier ruta no existente manda al Home */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </main>

        {/* 🔻 PIE DE PÁGINA GLOBAL (visible en todas las vistas públicas) */}
        <Footer />

        {/* ==========================================
            🤖 ASISTENTE VIRTUAL PERMANENTE (CHATBOT)
           ========================================== */}
        {/* Al estar fuera del enrutador, el chatbot flota en todas las paginas */}
        <Chatbot />
      </div>
    </Router>
  );
}