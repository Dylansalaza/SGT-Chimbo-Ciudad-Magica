import React, { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  BuildingLibraryIcon,
  ChatBubbleLeftRightIcon,
  UserCircleIcon,
  HomeIcon,
  MapIcon,
  MapPinIcon,
  WrenchScrewdriverIcon,
  CalendarDaysIcon,
  BuildingStorefrontIcon,
  ArrowLeftIcon,
  SparklesIcon,
  FireIcon,
} from '@heroicons/react/24/solid';

const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:3000/api';

// Frase de cierre que se agrega al final de toda respuesta del asistente.
const CLOSING = '¿Te puedo ayudar en algo más?';
function withClosing(text) {
  return `${text}\n\n${CLOSING}`;
}

// Palabras clave para detectar lugares de gastronomía
const FOOD_KEYS = ['gastro', 'restaurante', 'comida', 'cafetería', 'cafeteria',
                   'cocina', 'alimenta', 'comer', 'food', 'picante', 'mercado'];

// ─────────────────────────────────────────────────────────────
// Coordenadas de referencia para las secciones ESTÁTICAS del FAQ.
// Estos nodos (talleres de madera, armería de Tambán, hornado) NO
// corresponden a un registro de la BD, así que sus botones "Ver en el mapa"
// necesitan coordenadas explícitas; sin ellas el mapa se abre pero no centra
// en ningún punto. Los puntos del centro coinciden con el catálogo de lugares
// (Parque Central / Mercado central). El de Tambán es APROXIMADO (barrio en la
// vía al Guayco): ajústalo con el GPS real cuando exista.
// ─────────────────────────────────────────────────────────────
const COORDS = {
  centroChimbo: { lat: -1.6769, lng: -79.0389 }, // Parque Central / Bolívar y García Moreno
  mercado:      { lat: -1.6775, lng: -79.0378 }, // Mercado central (plaza gastronómica)
  tamban:       { lat: -1.6740, lng: -79.0420 }, // Barrio Tambán, vía al Guayco — ⚠ APROXIMADO, verificar GPS real
};

// Mapa de emojis usados como icono al inicio de las etiquetas de los botones
// del árbol de FAQ (root, STATIC_SECTIONS, buildFAQ) a iconos SVG reales.
// No toca los datos (btn.label sigue siendo el mismo string, así que la
// lógica de btnClass() que mira "←" para el estilo de "Volver" sigue intacta);
// solo cambia cómo se pinta la etiqueta en pantalla.
const LABEL_ICON_MAP = [
  [/^🏠\s*/, HomeIcon],
  [/^🗺️\s*/, MapIcon],
  [/^📍\s*/, MapPinIcon],
  [/^🍽️\s*/, FireIcon],
  [/^🛠️\s*/, WrenchScrewdriverIcon],
  [/^🔧\s*/, WrenchScrewdriverIcon],
  [/^📅\s*/, CalendarDaysIcon],
  [/^🏪\s*/, BuildingStorefrontIcon],
  [/^🪵\s*/, WrenchScrewdriverIcon],
  [/^🎆\s*/, SparklesIcon],
  [/^🐷\s*/, FireIcon],
  [/^🥘\s*/, FireIcon],
  [/^🎉\s*/, SparklesIcon],
  [/^🎊\s*/, SparklesIcon],
  [/^🎭\s*/, SparklesIcon],
  [/^←\s*/, ArrowLeftIcon],
];

function renderLabel(label) {
  for (const [re, Icon] of LABEL_ICON_MAP) {
    if (re.test(label)) {
      return (
        <span className="flex items-center gap-1.5">
          <Icon className="w-3.5 h-3.5 shrink-0" />{label.replace(re, '')}
        </span>
      );
    }
  }
  return label;
}

// Quita el emoji inicial de una etiqueta (para el eco en texto plano del
// mensaje del usuario, donde no tiene sentido mostrar un <Icon/>).
function stripLeadingIcon(label) {
  for (const [re] of LABEL_ICON_MAP) {
    if (re.test(label)) return label.replace(re, '');
  }
  return label;
}

function isFoodPlace(cat) {
  if (!cat) return false;
  const c = cat.toLowerCase();
  return FOOD_KEYS.some(k => c.includes(k));
}

// ═══════════════════════════════════════════════════════════
// SECCIONES ESTÁTICAS del FAQ (no vienen de la BD)
// ═══════════════════════════════════════════════════════════
const STATIC_SECTIONS = {
  // ── ARTESANÍAS ────────────────────────────────────────
  artesanias: {
    text: "**Artesanías de Chimbo**\n\n¿Qué te interesa conocer?",
    buttons: [
      { label: "🪵 Torneros de madera",   next: "madera" },
      { label: "🔧 Armería de Tambán",    next: "armeros" },
      { label: "🎆 Pirotecnia",           next: "pirotecnia" },
      { label: "🏠 Menú principal",        next: "root" },
    ],
  },
  madera: {
    text: "**Torneros de Madera**\n\nChimbo es reconocido por sus maestros torneros que elaboran figuras, muebles y utensilios en maderas finas de la región.",
    buttons: [
      { label: "📍 ¿Dónde comprar?",    next: "madera_compra" },
      { label: "← Volver",              next: "artesanias" },
      { label: "🏠 Menú principal",      next: "root" },
    ],
  },
  madera_compra: {
    text: "**Talleres de madera en Chimbo:**\n\n• Calle Bolívar y García Moreno\n• Feria artesanal del parque central (fines de semana)\n• Expo-ferias del GAD Municipal (fechas especiales)",
    buttons: [
      { label: "🗺️ Ver en el mapa",   action: "mapa", ...COORDS.centroChimbo, nombre: "Talleres de madera (centro de Chimbo)" },
      { label: "← Volver",             next: "madera" },
      { label: "🏠 Menú principal",     next: "root" },
    ],
  },
  armeros: {
    text: "**Armería de Tambán**\n\nTambán, barrio de San José de Chimbo, en la provincia de Bolívar, fue conocido durante décadas como la tierra de la armería en Ecuador. Desde inicios del siglo XX, en ese sitio se fabricaron escopetas, carabinas y revólveres artesanales.",
    buttons: [
      { label: "📍 Cómo llegar",        next: "tamban_ubicacion" },
      { label: "← Volver",              next: "artesanias" },
      { label: "🏠 Menú principal",      next: "root" },
    ],
  },
  tamban_ubicacion: {
    text: "**Tambán — Cómo llegar:**\n\nBarrio de San José de Chimbo, en la vía al Santuario del Guayco, a pocos minutos del centro.\nAccesible en vehículo o a pie.",
    buttons: [
      { label: "🗺️ Ver en el mapa",   action: "mapa", ...COORDS.tamban, nombre: "Tambán — Armería artesanal" },
      { label: "← Volver",             next: "armeros" },
      { label: "🏠 Menú principal",     next: "root" },
    ],
  },
  pirotecnia: {
    text: "**Pirotecnia Chimbeña**\n\nChimbo es cuna de maestros pirotécnicos que elaboran castillos, vacas locas y fuegos artificiales. Sus espectáculos son parte de las festividades culturales.",
    buttons: [
      { label: "📅 ¿Cuándo verla?",    next: "pirotecnia_cuando" },
      { label: "← Volver",             next: "artesanias" },
      { label: "🏠 Menú principal",     next: "root" },
    ],
  },
  pirotecnia_cuando: {
    text: "**Espectáculos Pirotécnicos — Fechas:**\n\n• Fiestas de San José (19 Marzo)\n• Cantonización (25 Mayo)\n• Año Nuevo (31 Diciembre)\n• Festivales culturales del GAD",
    buttons: [
      { label: "← Volver",         next: "pirotecnia" },
      { label: "🏠 Menú principal", next: "root" },
    ],
  },

  // ── EVENTOS ────────────────────────────────────────────
  eventos: {
    text: "**Eventos y Fiestas de Chimbo**\n\n¿Qué te interesa?",
    buttons: [
      { label: "🎉 Fiestas patronales",  next: "fiestas_patronales" },
      { label: "🎊 Cantonización",       next: "cantonizacion" },
      { label: "🎭 Eventos culturales",  next: "eventos_culturales" },
      { label: "🏠 Menú principal",      next: "root" },
    ],
  },
  fiestas_patronales: {
    text: "**Fiestas Patronales de San José**\n\nFecha: 19 de marzo\n\n• Procesiones y misas solemnes\n• Espectáculos pirotécnicos\n• Corridas de toros populares\n• Banda de pueblo y priostes\n• Feria gastronómica típica",
    buttons: [
      { label: "← Volver a eventos", next: "eventos" },
      { label: "🏠 Menú principal",   next: "root" },
    ],
  },
  cantonizacion: {
    text: "**Fiestas de Cantonización de Chimbo**\n\nFecha: 25 de mayo\n\n• Sesión solemne del GAD Municipal\n• Desfile cívico y militar\n• Feria agro-artesanal\n• Concursos de música y danza\n• Noche de gala y coronación",
    buttons: [
      { label: "← Volver a eventos", next: "eventos" },
      { label: "🏠 Menú principal",   next: "root" },
    ],
  },
  eventos_culturales: {
    text: "**Eventos Culturales en Chimbo:**\n\n• Festival de la Canción Bolivarense\n• Exposición de fotografía patrimonial\n• Talleres de artesanía para visitantes\n• Feria gastronómica del hornado\n\nPara la agenda completa visita la sección de Eventos del portal.",
    buttons: [
      { label: "← Volver a eventos", next: "eventos" },
      { label: "🏠 Menú principal",   next: "root" },
    ],
  },

  // ── GASTRONOMÍA: nodos estáticos (se complementan con dinámicos) ──
  hornado: {
    text: "**Hornado Chimbeño — Patrimonio Gastronómico**\n\nPreparado en hornos de leña durante 6 horas. Se sirve con:\n• Mote cocido\n• Tortillas de papa\n• Agrio (salsa tradicional)\n• Ají chimbeño\n\nDeclarado patrimonio gastronómico de la provincia de Bolívar.",
    buttons: [
      { label: "🏪 ¿Dónde comerlo?",      next: "gastronomia" },
      { label: "🗺️ Ver en el mapa",       action: "mapa", ...COORDS.mercado, nombre: "Hornado — Mercado central" },
      { label: "← Volver a gastronomía",  next: "gastronomia" },
      { label: "🏠 Menú principal",        next: "root" },
    ],
  },
  platos_tipicos: {
    text: "**Platos típicos de Chimbo:**\n\n• Hornado con mote y tortillas\n• Caldo de gallina criolla\n• Tamales de maíz\n• Fritada chimbeña\n• Dulce de leche artesanal\n• Chicha de jora tradicional",
    buttons: [
      { label: "🏪 ¿Dónde comer?",        next: "gastronomia" },
      { label: "← Volver a gastronomía",  next: "gastronomia" },
      { label: "🏠 Menú principal",        next: "root" },
    ],
  },
};

// ═══════════════════════════════════════════════════════════
// CONSTRUCTOR DINÁMICO: crea nodos por lugar desde la API
// ═══════════════════════════════════════════════════════════
function buildPlaceNode(place, backTo) {
  const lines = [];
  lines.push(`**${place.nombre}**`);
  if (place.descripcion) lines.push('\n' + place.descripcion.slice(0, 200) + (place.descripcion.length > 200 ? '…' : ''));
  if (place.direccion)   lines.push(`\n\n**Dirección:** ${place.direccion}`);
  if (place.horario)     lines.push(`**Horario:** ${place.horario}`);
  if (place.precio)      lines.push(`**Precio:** ${place.precio}`);
  if (place.telefono)    lines.push(`**Teléfono:** ${place.telefono}`);

  const lat = place.lat ? parseFloat(place.lat) : null;
  const lng = place.lng ? parseFloat(place.lng) : null;
  const hasCoords = lat && lng && !isNaN(lat) && !isNaN(lng);

  const buttons = [];
  // Pasar coordenadas al botón para que ChimboMap pueda centrar el punto
  buttons.push({
    label: "🗺️ Ver en el mapa",
    action: "mapa",
    lat, lng,
    placeId: place.id,
    nombre: place.nombre,
  });
  if (hasCoords) {
    buttons.push({
      label: "📍 Cómo llegar",
      action: "googlemaps",
      lat, lng,
    });
  }
  buttons.push({ label: "← Volver", next: backTo });
  buttons.push({ label: "🏠 Menú principal", next: "root" });

  return { text: lines.join('\n'), buttons };
}

function buildFAQ(places) {
  const faq = { ...STATIC_SECTIONS };

  const sitioPlaces = places.filter(p => !isFoodPlace(p.categoria));
  const foodPlaces  = places.filter(p => isFoodPlace(p.categoria));

  // ── Nodos individuales de cada lugar ──────────────────
  sitioPlaces.forEach(p => {
    faq[`place_${p.id}`] = buildPlaceNode(p, 'sitios');
  });
  foodPlaces.forEach(p => {
    faq[`food_${p.id}`] = buildPlaceNode(p, 'gastronomia');
  });

  // ── Nodo "sitios" dinámico ─────────────────────────────
  const sitioButtons = sitioPlaces.slice(0, 6).map(p => ({
    label: `📍 ${p.nombre}`,
    next: `place_${p.id}`,
  }));
  sitioButtons.push({ label: "🏠 Menú principal", next: "root" });

  faq.sitios = {
    text: sitioPlaces.length > 0
      ? "**Sitios Turísticos de Chimbo**\n\nElige un lugar para ver su dirección y acceder al mapa:"
      : "**Sitios Turísticos**\n\nPronto habrá lugares disponibles. ¡Vuelve pronto!",
    buttons: sitioButtons,
  };

  // ── Nodo "gastronomia" dinámico ────────────────────────
  const gastronomiaButtons = [];

  // Botón principal: ver todos los lugares de comer en el mapa
  gastronomiaButtons.push({
    label: "🗺️ Lugares de comer en el mapa",
    action: "mapa_food",
    foodPlaces,          // se pasa la lista para centrar en el primero
  });

  // Luego cada restaurante registrado en la BD (con dirección y cómo llegar)
  foodPlaces.slice(0, 4).forEach(p => {
    gastronomiaButtons.push({ label: `🍽️ ${p.nombre}`, next: `food_${p.id}` });
  });

  // Información gastronómica cultural
  gastronomiaButtons.push({ label: "🐷 Hornado Chimbeño", next: "hornado" });
  gastronomiaButtons.push({ label: "🥘 Platos típicos",    next: "platos_tipicos" });
  gastronomiaButtons.push({ label: "🏠 Menú principal",    next: "root" });

  const gastronomiaText = foodPlaces.length > 0
    ? "**Gastronomía de Chimbo**\n\nEncuentra restaurantes y locales de comida:"
    : "**Gastronomía de Chimbo**\n\nAún no hay restaurantes registrados en el sistema:";

  faq.gastronomia = {
    text: gastronomiaText,
    buttons: gastronomiaButtons,
  };

  // ── Nodo raíz ──────────────────────────────────────────
  faq.root = {
    text: "¡Hola! Soy tu asistente virtual de **Chimbo Ciudad Mágica**.\n\n¿En qué puedo ayudarte hoy?",
    buttons: [
      { label: "🗺️ Sitios Turísticos",    next: "sitios" },
      { label: "🍽️ Gastronomía",          next: "gastronomia" },
      { label: "🛠️ Artesanías",           next: "artesanias" },
      { label: "📅 Eventos y Fiestas",    next: "eventos" },
    ],
  };

  return faq;
}

// ════════════════════════════════════════════════════════════
// COMPONENTE PRINCIPAL
// ════════════════════════════════════════════════════════════
// Quita acentos y pasa a minúsculas para comparar texto de forma flexible
function normalizar(txt) {
  return (txt || '')
    .toString()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .trim();
}

// Busca la palabra clave que mejor coincida con el mensaje del usuario.
// Si varias coinciden, gana la más larga (más específica).
function buscarFaq(mensaje, keywordFaqs) {
  const msgNorm = normalizar(mensaje);
  if (!msgNorm) return null;

  let mejor = null;
  for (const faq of keywordFaqs) {
    const kwNorm = normalizar(faq.keyword);
    if (kwNorm && msgNorm.includes(kwNorm)) {
      if (!mejor || kwNorm.length > normalizar(mejor.keyword).length) {
        mejor = faq;
      }
    }
  }
  return mejor;
}

function Chatbot() {
  const [isOpen, setIsOpen]     = useState(false);
  const [messages, setMessages] = useState([]);
  const [input, setInput]       = useState('');
  const [faq, setFaq]           = useState(null);   // se llena al cargar lugares
  const [keywordFaqs, setKeywordFaqs] = useState([]); // palabras clave configuradas en el panel admin
  const [loading, setLoading]   = useState(true);
  const [aiThinking, setAiThinking] = useState(false); // true mientras esperamos la respuesta de la IA
  const messagesEndRef           = useRef(null);
  const navigate                 = useNavigate();

  // Cargar lugares de la API y construir el FAQ dinámico
  useEffect(() => {
    fetch(`${API_URL}/tourist-places`)
      .then(r => r.json())
      .then(data => {
        const places = Array.isArray(data) ? data : (data.data ?? []);
        setFaq(buildFAQ(places));
      })
      .catch(() => setFaq(buildFAQ([])))
      .finally(() => setLoading(false));
  }, []);

  // Cargar las palabras clave configuradas en el panel admin (tabla chat_faqs)
  useEffect(() => {
    fetch(`${API_URL}/chat-faqs`)
      .then(r => r.json())
      .then(data => setKeywordFaqs(Array.isArray(data) ? data : []))
      .catch(() => setKeywordFaqs([]));
  }, []);

  // Al abrir el chat mostrar nodo raíz
  useEffect(() => {
    if (isOpen && messages.length === 0 && faq) {
      setMessages([makeBotMsg(faq.root, 'root', faq)]);
    }
  }, [isOpen, faq]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  // Toda respuesta del bot cierra invitando a seguir preguntando, salvo el
  // nodo raíz (que ya trae su propia pregunta de apertura "¿En qué puedo
  // ayudarte hoy?" — repetir ahí sonaría redundante).
  function makeBotMsg(node, nodeId, faqRef) {
    return {
      id: Date.now() + Math.random(),
      sender: 'bot',
      text: nodeId === 'root' ? node.text : withClosing(node.text),
      buttons: node.buttons || [],
      nodeId,
      timestamp: new Date(),
    };
  }

  function handleOption(btn) {
    // ── Acción especial: mapa con todos los lugares de comer ──
    if (btn.action === 'mapa_food') {
      // Centra en el primer restaurante disponible (si hay)
      const first = btn.foodPlaces?.[0];
      navigate('/mapa', {
        state: first?.lat && first?.lng ? {
          lat:     parseFloat(first.lat),
          lng:     parseFloat(first.lng),
          placeId: first.id,
          nombre:  first.nombre,
          showFood: true,
        } : { showFood: true },
      });
      setIsOpen(false);
      return;
    }

    // ── Acción especial: navegar al mapa interno (con coordenadas) ──
    if (btn.action === 'mapa') {
      navigate('/mapa', {
        state: {
          lat:     btn.lat,
          lng:     btn.lng,
          placeId: btn.placeId,
          nombre:  btn.nombre,
        },
      });
      setIsOpen(false);
      return;
    }
    // ── Acción especial: Google Maps navegación completa ─────
    if (btn.action === 'googlemaps') {
      // Google Maps detecta la ubicación del usuario automáticamente,
      // muestra la ruta completa: distancia, tiempo estimado, giro a giro.
      // Nota: en móvil abre la app de Google Maps directamente.
      window.open(
        `https://www.google.com/maps/dir/?api=1&destination=${btn.lat},${btn.lng}&travelmode=driving`,
        '_blank'
      );
      return;
    }

    // ── Navegación FAQ normal ─────────────────────────────
    const userMsg = {
      id: Date.now() + Math.random(),
      sender: 'user',
      text: stripLeadingIcon(btn.label),
      buttons: [],
      timestamp: new Date(),
    };
    const nextNode = faq[btn.next] || faq.root;
    const botMsg   = makeBotMsg(nextNode, btn.next, faq);
    setMessages(prev => [...prev, userMsg, botMsg]);
  }

  async function handleTextSend(e) {
    e.preventDefault();
    const txt = input.trim();
    if (!txt || aiThinking) return;
    setInput('');

    const userMsg = {
      id: Date.now() + Math.random(),
      sender: 'user',
      text: txt,
      buttons: [],
      timestamp: new Date(),
    };
    setMessages(prev => [...prev, userMsg]);

    // 1) Buscar coincidencia rápida entre lo escrito y las palabras clave del panel admin
    //    (instantáneo y sin gastar cuota de IA).
    const coincidencia = buscarFaq(txt, keywordFaqs);
    if (coincidencia) {
      const respuestaMsg = {
        ...makeBotMsg(faq.root, 'root', faq),
        text: withClosing(coincidencia.answer),
      };
      setMessages(prev => [...prev, respuestaMsg]);
      return;
    }

    // 2) Si no hay coincidencia exacta, preguntarle a la IA (Gemini) con
    //    el contexto real del portal, para que responda de forma natural.
    setAiThinking(true);
    try {
      const resp = await fetch(`${API_URL}/chat-ai`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: txt }),
      });
      const data = await resp.json();

      const respuestaMsg = resp.ok
        ? { ...makeBotMsg(faq.root, 'root', faq), text: withClosing(data.answer) }
        : {
            ...makeBotMsg(faq.root, 'root', faq),
            text: withClosing(`${data.error || 'No pude responder eso ahora mismo.'}\n\nMientras tanto, puedes usar el menú:`),
          };
      setMessages(prev => [...prev, respuestaMsg]);
    } catch (err) {
      setMessages(prev => [...prev, {
        ...makeBotMsg(faq.root, 'root', faq),
        text: withClosing('No pude conectarme con el asistente de IA. Revisa tu conexión o usa el menú:'),
      }]);
    } finally {
      setAiThinking(false);
    }
  }

  function resetChat() {
    setMessages(faq ? [makeBotMsg(faq.root, 'root', faq)] : []);
  }

  function renderText(text) {
    return text.split('\n').map((line, i, arr) => {
      const parts = line.split('**');
      return (
        <React.Fragment key={i}>
          {parts.map((part, j) =>
            j % 2 === 1
              ? <strong key={j} className="font-bold">{part}</strong>
              : part
          )}
          {i < arr.length - 1 && <br />}
        </React.Fragment>
      );
    });
  }

  // Estilo del botón según su tipo
  function btnClass(btn) {
    if (btn.action === 'mapa')
      return 'bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700';
    if (btn.action === 'googlemaps')
      return 'bg-orange-500 text-white border-orange-500 hover:bg-orange-600';
    if (btn.next === 'root')
      return 'bg-[#00335c] text-white border-[#00335c] hover:bg-[#004080]';
    if (btn.label.startsWith('←'))
      return 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200';
    return 'bg-white text-slate-800 border-slate-200 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700';
  }

  return (
    <>
      {/* ── Botón flotante ── */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="fixed bottom-6 right-6 bg-gradient-to-r from-[#00335c] to-blue-700 text-white rounded-full p-4 shadow-2xl z-50 transition-all duration-300 transform hover:scale-110 active:scale-95 flex items-center justify-center border-2 border-white"
        title="Asistente Virtual"
      >
        {isOpen ? (
          <svg xmlns="http://www.w3.org/2000/svg" className="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M6 18L18 6M6 6l12 12" />
          </svg>
        ) : (
          <div className="relative flex items-center justify-center animate-pulse">
            <BuildingLibraryIcon className="w-7 h-7" />
            <ChatBubbleLeftRightIcon className="absolute -top-2 -right-2 w-3.5 h-3.5" />
          </div>
        )}
      </button>

      {/* ── Ventana del chat ── */}
      {isOpen && (
        <div className="fixed z-50 bg-white rounded-2xl shadow-2xl flex flex-col border border-slate-200 overflow-hidden
          left-3 right-3 bottom-24 h-[26rem] max-h-[65vh]
          sm:left-auto sm:right-6 sm:w-80 sm:h-[28rem] sm:max-h-none">

          {/* Header */}
          <div className="bg-[#00335c] text-white p-4 flex justify-between items-center shadow-md">
            <div className="flex items-center gap-3">
              <div className="w-11 h-11 bg-white/10 rounded-full flex items-center justify-center border border-white/20">
                <BuildingLibraryIcon className="w-6 h-6" />
              </div>
              <div>
                <h3 className="font-bold text-sm tracking-wide flex items-center gap-1.5 whitespace-nowrap">
                  Asistente Virtual
                  <span className="text-[10px] bg-blue-500 text-white px-1.5 py-0.5 rounded font-mono">FAQ</span>
                </h3>
                <p className="text-xs text-slate-300">Turismo — Chimbo</p>
              </div>
            </div>
            <div className="flex gap-1.5">
              <button onClick={resetChat} className="p-1.5 rounded-lg hover:bg-white/10 text-slate-200 transition" title="Reiniciar">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clipRule="evenodd" />
                </svg>
              </button>
              <button onClick={() => setIsOpen(false)} className="p-1.5 rounded-lg hover:bg-white/10 text-slate-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                </svg>
              </button>
            </div>
          </div>

          {/* Área de mensajes */}
          <div className="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50">
            {loading && (
              <div className="flex justify-center items-center h-full">
                <div className="text-slate-400 text-sm animate-pulse flex items-center gap-1.5"><BuildingLibraryIcon className="w-4 h-4" /> Cargando información…</div>
              </div>
            )}

            {!loading && messages.map((msg) => (
              <div key={msg.id} className={`flex ${msg.sender === 'user' ? 'justify-end' : 'justify-start'} items-end gap-2`}>

                {/* Avatar bot */}
                {msg.sender === 'bot' && (
                  <div className="w-7 h-7 bg-[#00335c] text-white rounded-full flex items-center justify-center flex-shrink-0">
                    <BuildingLibraryIcon className="w-4 h-4" />
                  </div>
                )}

                <div className={`max-w-[85%] ${msg.sender === 'user' ? 'items-end' : 'items-start'} flex flex-col gap-2`}>
                  {/* Burbuja */}
                  <div className={`p-3.5 rounded-2xl shadow-sm text-sm leading-relaxed
                    ${msg.sender === 'user'
                      ? 'bg-blue-600 text-white rounded-br-none'
                      : 'bg-white text-slate-800 rounded-bl-none border border-slate-200'
                    }`}>
                    {renderText(msg.text)}
                    <div className={`text-[9px] mt-1 opacity-60 text-right
                      ${msg.sender === 'user' ? 'text-blue-100' : 'text-slate-400'}`}>
                      {msg.timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                    </div>
                  </div>

                  {/* Botones */}
                  {msg.sender === 'bot' && msg.buttons.length > 0 && (
                    <div className="flex flex-col gap-1.5 w-full">
                      {msg.buttons.map((btn, idx) => (
                        <button
                          key={idx}
                          onClick={() => handleOption(btn)}
                          className={`text-left text-xs font-medium px-3.5 py-2 rounded-xl border transition-all duration-150 shadow-sm ${btnClass(btn)}`}
                        >
                          {renderLabel(btn.label)}
                        </button>
                      ))}
                    </div>
                  )}
                </div>

                {/* Avatar usuario */}
                {msg.sender === 'user' && (
                  <div className="w-7 h-7 bg-slate-400 rounded-full flex items-center justify-center flex-shrink-0">
                    <UserCircleIcon className="w-5 h-5 text-white" />
                  </div>
                )}
              </div>
            ))}

            {/* Indicador "escribiendo…" mientras la IA responde */}
            {aiThinking && (
              <div className="flex justify-start items-end gap-2">
                <div className="w-7 h-7 bg-[#00335c] text-white rounded-full flex items-center justify-center flex-shrink-0">
                  <BuildingLibraryIcon className="w-4 h-4" />
                </div>
                <div className="bg-white border border-slate-200 rounded-2xl rounded-bl-none px-4 py-3 shadow-sm flex gap-1 items-center">
                  <span className="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce [animation-delay:-0.3s]"></span>
                  <span className="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce [animation-delay:-0.15s]"></span>
                  <span className="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce"></span>
                </div>
              </div>
            )}

            <div ref={messagesEndRef} />
          </div>

          {/* Input */}
          <form onSubmit={handleTextSend} className="border-t border-slate-200 p-3 flex gap-2 bg-white">
            <input
              type="text"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              disabled={loading || !faq || aiThinking}
              placeholder="Escribe tu consulta (ej: horario, precio, wifi)…"
              className="flex-1 p-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 text-slate-700 disabled:opacity-50"
            />
            <button
              type="submit"
              disabled={loading || !faq || aiThinking}
              className="bg-[#00335c] hover:bg-blue-800 text-white px-4 py-2 text-sm rounded-xl transition shadow-sm disabled:opacity-50"
            >
              Enviar
            </button>
          </form>
        </div>
      )}
    </>
  );
}

export default Chatbot;
