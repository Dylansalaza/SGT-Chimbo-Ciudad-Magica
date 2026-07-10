<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - Sistema de Gestión Turística</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('media/logo/logo-icon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                    },
                },
            },
        };
    </script>
    {{-- Font Awesome "SVG + JS": convierte cada <i class="fas fa-..."> en un <svg> real
         en el DOM (no en una fuente de icono), sin tener que tocar el markup existente. --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js" crossorigin="anonymous"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        :root {
            --color-navy: #00294d;       /* Azul Marino Corporativo Principal */
            --color-red-accent: #e11d48; /* Rojo Institucional de Alto Contraste */
            --color-bg-clean: #f1f5f9;   /* Fondo gris claro nítido para el área de trabajo */
        }

        body {
            font-family: 'Manrope', sans-serif;
            background-color: var(--color-bg-clean);
        }

        /* Estilos del Sidebar con el color corporativo exacto */
        .sidebar-corporate {
            background-color: var(--color-navy);
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.15);
        }

        /* Enlaces de navegación con alto contraste y enfoque nítido */
        .nav-link-premium {
            font-weight: 600;
            color: #cbd5e1;
            transition: all 0.2s ease-in-out;
            border-left: 4px solid transparent;
        }
        .nav-link-premium:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.08);
            border-left-color: var(--color-red-accent);
            padding-left: 1.25rem;
        }
        .nav-link-premium.active {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.12);
            border-left-color: var(--color-red-accent);
        }

        /* Componentes de interfaz compartidos */
        .preview-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin: 5px;
            border-radius: 8px;
            border: 2px solid #cbd5e1;
        }
        
        .dropzone-area {
            border: 2px dashed #00294d;
            border-radius: 1rem;
            background: #ffffff;
            min-height: 200px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .dropzone-area:hover {
            background: #f8fafc;
            border-color: var(--color-red-accent);
        }

        #map {
            height: 400px;
            width: 100%;
            border-radius: 1rem;
            z-index: 1;
            border: 2px solid #94a3b8;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }

        /* Líneas de diseño del Imagotipo SMC */
        .logo-speed-lines-smc {
            background: linear-gradient(to bottom, 
                #fff 0px, #fff 1.5px, transparent 1.5px, transparent 3.5px,
                #fff 3.5px, #fff 5px, transparent 5px, transparent 7px,
                #fff 7px, #fff 8.5px, transparent 8.5px, transparent 10.5px,
                #fff 10.5px, #fff 12px);
            height: 12px;
            width: 32px;
        }

        /* Sombras potentes configuradas para las tarjetas contenedoras */
        .card-premium-shadow {
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.07), 0 0 25px 1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }

        /* ===== Transición de entrada de cada vista =====
           Cada página del panel entra con un fade + leve desplazamiento,
           rápido (220 ms) y con ease-out fuerte. Solo anima opacity y
           transform (GPU). Se desactiva si el usuario prefiere menos motion. */
        @keyframes vista-enter {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .vista-enter { animation: vista-enter 220ms cubic-bezier(0.16, 1, 0.3, 1) both; }
        @media (prefers-reduced-motion: reduce) {
            .vista-enter { animation: none; }
        }

        /* ===== Sidebar colapsable ===== */
        #sidebar { transition: width 0.25s ease; }
        #sidebar.collapsed { width: 5rem !important; }
        #sidebar.collapsed .sidebar-collapsible { display: none !important; }
        #sidebar.collapsed .sidebar-mini { display: flex !important; }
        #sidebar.collapsed nav a {
            justify-content: center;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        #sidebar.collapsed nav a:hover { padding-left: 0.5rem !important; border-left-color: transparent; }
        #sidebar.collapsed nav a span { display: none; }
        #sidebar.collapsed nav a i { font-size: 1.25rem; }
        /* La flecha del botón apunta hacia afuera según el estado */
        #sidebar .sidebar-toggle-btn i { transition: transform 0.25s ease; }
        #sidebar.collapsed .sidebar-toggle-btn i { transform: rotate(180deg); }
    </style>
</head>
<body class="antialiased select-none text-slate-950">

    <div class="flex h-screen overflow-hidden">
        
        {{-- Sidebar Izquierdo --}}
        <aside id="sidebar" class="relative w-64 sidebar-corporate text-white flex flex-col z-20 shrink-0">

            {{-- Botón flotante para abrir/cerrar el menú (borde derecho, centrado) --}}
            <button type="button" onclick="toggleSidebar()" title="Abrir/cerrar menú" aria-label="Abrir o cerrar menú"
                    class="sidebar-toggle-btn group absolute top-1/2 -right-5 -translate-y-1/2 z-40 w-11 h-11 rounded-full
                           flex items-center justify-center text-white bg-[#00294d] hover:bg-[#e11d48]
                           border-2 border-white shadow-lg transition-all duration-200 active:scale-90">
                <i class="fas fa-chevron-left text-base group-hover:scale-110 transition-transform"></i>
            </button>

            <div class="p-5 pt-5 border-b border-white/10 flex flex-col items-center justify-center bg-black/10">
                {{-- Logo completo (visible cuando el menú está abierto) --}}
                <div class="sidebar-collapsible flex items-center justify-center">
                    <img src="{{ asset('media/logo/logo-horizontal-dark.svg') }}" alt="SGT Chimbo — Sistema de Gestión Turístico" class="h-11 w-auto">
                </div>
                {{-- Logo mini (visible solo cuando el menú está cerrado) --}}
                <div class="sidebar-mini hidden items-center justify-center">
                    <img src="{{ asset('media/logo/logo-icon.svg') }}" alt="SGT Chimbo" class="h-9 w-9 rounded-lg">
                </div>
            </div>
            
            <nav class="flex-1 mt-6 space-y-1 px-2">
                {{-- Dashboard: visible para ambos roles --}}
                <a href="{{ route('admin.dashboard') }}" title="Dashboard" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt w-5 text-center text-base"></i>
                    <span>Dashboard</span>
                </a>

                {{-- Sección del Admin de Turismo --}}
                @if(auth()->user()?->isAdminTurismo())
                <a href="{{ route('admin.home.edit') }}" title="Editar Home" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.home.*') ? 'active' : '' }}">
                    <i class="fas fa-house w-5 text-center text-base"></i>
                    <span>Editar Home</span>
                </a>
                <a href="{{ route('admin.eventos.index') }}" title="Eventos" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.eventos.*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-alt w-5 text-center text-base"></i>
                    <span>Eventos</span>
                </a>
                <a href="{{ route('admin.noticias.index') }}" title="Noticias" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.noticias.*') ? 'active' : '' }}">
                    <i class="fas fa-newspaper w-5 text-center text-base"></i>
                    <span>Noticias</span>
                </a>
                <a href="{{ route('admin.galerias.index') }}" title="Galerías" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.galerias.*') ? 'active' : '' }}">
                    <i class="fas fa-images w-5 text-center text-base"></i>
                    <span>Galerías</span>
                </a>
                <a href="{{ route('admin.lugares.index') }}" title="Lugares Turísticos" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.lugares.*') ? 'active' : '' }}">
                    <i class="fas fa-map-marker-alt w-5 text-center text-base"></i>
                    <span>Lugares Turísticos</span>
                </a>
                <a href="{{ route('admin.categorias.index') }}" title="Categorías" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.categorias.*') ? 'active' : '' }}">
                    <i class="fas fa-tags w-5 text-center text-base"></i>
                    <span>Categorías</span>
                </a>
                <a href="{{ route('admin.reportes.visitas') }}" title="Reportes" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.reportes.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line w-5 text-center text-base"></i>
                    <span>Reportes</span>
                </a>
                @endif

                {{-- Sección del Administrador --}}
                @if(auth()->user()?->isAdministrador())
                <a href="{{ route('admin.usuarios.index') }}" title="Usuarios" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
                    <i class="fas fa-users w-5 text-center text-base"></i>
                    <span>Usuarios</span>
                </a>
                @endif
            </nav>
            
            <div class="p-4 border-t border-white/10 bg-black/10">
                <button
                    type="button"
                    onclick="ejecutarCierreSesionCorporativo(event)"
                    class="w-full px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold uppercase tracking-wider rounded-lg text-xs transition-all duration-150 flex items-center justify-center gap-2 shadow-md shadow-rose-900/30 active:scale-[0.98]"
                >
                    <i class="fas fa-sign-out-alt text-sm"></i> <span class="sidebar-collapsible">Cerrar Sesión</span>
                </button>
            </div>
        </aside>

        {{-- Área de Contenido Principal sin paddings forzados --}}
        <main class="flex-1 overflow-y-auto relative z-10 flex flex-col bg-[#f1f5f9]">
            <div class="vista-enter flex-1 flex flex-col">
                @yield('content')
            </div>
        </main>
        
    </div>
    
    @stack('scripts')

    {{-- ===== Alerta centrada global (éxito / error / aviso / información) =====
         Mismo lenguaje visual que el modal de confirmación de abajo: tarjeta
         blanca centrada, ícono circular por tipo, un solo diseño para todo
         el panel (reemplaza los toasts de la esquina y el alert() nativo). --}}
    <div id="modal-alerta" class="hidden fixed inset-0 z-[1000] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="cerrarModalAlerta()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-8 text-center animate-[fadeInScale_.2s_ease]">
            <div id="modal-alerta-icon-wrap" class="mx-auto mb-5 w-16 h-16 rounded-full flex items-center justify-center">
                <i id="modal-alerta-icon" class="fas text-2xl"></i>
            </div>
            <h3 id="modal-alerta-title" class="text-xl font-black text-slate-800 mb-2"></h3>
            <p id="modal-alerta-msg" class="text-sm text-slate-500 mb-7 leading-relaxed"></p>
            <button type="button" onclick="cerrarModalAlerta()" class="px-8 py-2.5 rounded-xl bg-[#00294d] hover:bg-[#003d73] text-white text-sm font-black transition-all shadow-md">
                Aceptar
            </button>
            <div class="h-1 rounded-full bg-slate-100 mt-6 overflow-hidden">
                <div id="modal-alerta-progress" class="h-full rounded-full"></div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <script>document.addEventListener('DOMContentLoaded',()=>showAlerta('success',@json(session('success'))));</script>
    @endif
    @if(session('error'))
    <script>document.addEventListener('DOMContentLoaded',()=>showAlerta('error',@json(session('error'))));</script>
    @endif
    @if(session('warning'))
    <script>document.addEventListener('DOMContentLoaded',()=>showAlerta('warning',@json(session('warning'))));</script>
    @endif
    @if(session('info'))
    <script>document.addEventListener('DOMContentLoaded',()=>showAlerta('info',@json(session('info'))));</script>
    @endif
    @if($errors->any())
    <script>document.addEventListener('DOMContentLoaded',()=>showAlerta('error',@json($errors->first())));</script>
    @endif

    {{-- ===== Modal de inactividad ===== --}}
    <div id="modal-inactividad" class="hidden fixed inset-0 z-[1001] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 text-center" style="animation:fadeInScale .3s cubic-bezier(.22,1,.36,1) forwards">
            {{-- Ícono animado --}}
            <div class="mx-auto mb-5 w-20 h-20 rounded-full bg-amber-50 border-4 border-amber-200 flex items-center justify-center relative">
                <i class="fas fa-clock text-3xl text-amber-500"></i>
                {{-- Anillo de cuenta regresiva --}}
                <svg class="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 80 80">
                    <circle cx="40" cy="40" r="36" fill="none" stroke="#fde68a" stroke-width="4"/>
                    <circle id="inac-ring" cx="40" cy="40" r="36" fill="none" stroke="#f59e0b" stroke-width="4"
                        stroke-dasharray="226" stroke-dashoffset="0"
                        style="transition:stroke-dashoffset 1s linear"/>
                </svg>
            </div>
            <h3 class="text-xl font-black text-slate-800 mb-1">¿Sigues ahí?</h3>
            <p class="text-sm text-slate-500 mb-2 leading-relaxed">Tu sesión cerrará por inactividad en</p>
            <div class="text-5xl font-black text-amber-500 my-4 tabular-nums" id="inac-countdown">60</div>
            <p class="text-xs text-slate-400 mb-7">segundos</p>
            <div class="flex gap-3 justify-center">
                <button onclick="ejecutarCierreSesionCorporativo()"
                    class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-500 hover:bg-slate-50 transition-all">
                    Cerrar sesión
                </button>
                <button onclick="renovarSesion()"
                    class="px-6 py-2.5 rounded-xl bg-[#00294d] hover:bg-[#003d73] text-white text-sm font-black transition-all shadow-md flex items-center gap-2">
                    <i class="fas fa-rotate-right text-xs"></i> Seguir conectado
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Modal de confirmación global ===== --}}
    <div id="modal-confirmar" class="hidden fixed inset-0 z-[999] flex items-center justify-center p-4">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="cerrarModalConfirmar()"></div>
        {{-- Tarjeta --}}
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 text-center animate-[fadeInScale_.2s_ease]">
            {{-- Icono --}}
            <div class="mx-auto mb-5 w-16 h-16 rounded-full bg-rose-50 flex items-center justify-center">
                <i class="fas fa-trash-alt text-2xl text-rose-500"></i>
            </div>
            {{-- Título (texto dinámico: "eliminar" para borrados reales, "dar de baja" para desactivaciones reversibles) --}}
            <h3 id="modal-confirmar-title" class="text-xl font-black text-slate-800 mb-2">¿Confirmar eliminación?</h3>
            {{-- Mensaje dinámico --}}
            <p id="modal-confirmar-msg" class="text-sm text-slate-500 mb-7 leading-relaxed">Esta acción no se puede deshacer.</p>
            {{-- Botones --}}
            <div class="flex gap-3 justify-center">
                <button onclick="cerrarModalConfirmar()"
                    class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all">
                    Cancelar
                </button>
                <button id="modal-confirmar-btn"
                    class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-black transition-all shadow-md shadow-rose-200 flex items-center gap-2">
                    <i id="modal-confirmar-btn-icon" class="fas fa-trash-alt text-xs"></i>
                    <span id="modal-confirmar-btn-texto">Eliminar</span>
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(.92); }
            to   { opacity: 1; transform: scale(1); }
        }
    </style>

    <script>
        // ===== Menú lateral colapsable =====
        function toggleSidebar() {
            const sb = document.getElementById('sidebar');
            sb.classList.toggle('collapsed');
            localStorage.setItem('sidebar_collapsed', sb.classList.contains('collapsed') ? '1' : '0');
        }
        // Restaurar estado guardado al cargar
        (function () {
            if (localStorage.getItem('sidebar_collapsed') === '1') {
                document.getElementById('sidebar')?.classList.add('collapsed');
            }
        })();
    </script>

    <script>
        // ===== Helper: cambiar el ícono de Font Awesome de un elemento ya
        // renderizado =====
        // El kit "SVG + JS" de Font Awesome convierte cada <i class="fas fa-x">
        // en un <svg> apenas carga la página. Después de esa conversión, cambiar
        // el className del elemento ya NO redibuja el ícono (el <i> original ya
        // no existe). Por eso, para íconos que cambian dinámicamente (alerta y
        // confirmación, según el tipo de acción), hay que reemplazar el nodo por
        // un <i> nuevo y forzar a Font Awesome a reconvertirlo.
        function _setIconoFA(id, clases) {
            const actual = document.getElementById(id);
            if (!actual) return;
            const nuevo = document.createElement('i');
            nuevo.id = id;
            nuevo.className = clases;
            actual.replaceWith(nuevo);
            window.FontAwesome?.dom?.i2svg();
        }
    </script>

    <script>
        // ===== Alerta centrada (reemplaza el alert() nativo y los toasts) =====
        // Un único diálogo centrado para success/error/warning/info, con el
        // mismo look que el modal de confirmación. Si llega una alerta
        // mientras otra está visible, se encola y se muestra a continuación.
        const ALERTA_CFG = {
            success: { icono: 'fa-circle-check',        iconBg: 'bg-emerald-50', iconColor: 'text-emerald-500', barra: 'bg-emerald-500', titulo: 'Éxito' },
            error:   { icono: 'fa-circle-xmark',         iconBg: 'bg-rose-50',    iconColor: 'text-rose-500',    barra: 'bg-rose-500',    titulo: 'Error' },
            warning: { icono: 'fa-triangle-exclamation', iconBg: 'bg-amber-50',   iconColor: 'text-amber-500',   barra: 'bg-amber-500',   titulo: 'Atención' },
            info:    { icono: 'fa-circle-info',          iconBg: 'bg-blue-50',    iconColor: 'text-blue-500',    barra: 'bg-blue-500',    titulo: 'Información' },
        };
        const ALERTA_DURACION = 4500;
        let _alertaCola  = [];
        let _alertaTimer = null;

        // "duracion" es opcional (ms); por defecto ALERTA_DURACION. Útil para
        // avisos más largos de leer (ej. resultado de una importación).
        function showAlerta(type, message, duracion) {
            const texto = String(message).replace(/^[✅⚠️ℹ️\s]+/, '');
            _alertaCola.push({ type: ALERTA_CFG[type] ? type : 'info', message: texto, duracion: duracion || ALERTA_DURACION });
            if (document.getElementById('modal-alerta').classList.contains('hidden')) {
                _procesarColaAlerta();
            }
        }

        function _procesarColaAlerta() {
            if (_alertaCola.length === 0) return;
            const { type, message, duracion } = _alertaCola.shift();
            const c = ALERTA_CFG[type] || ALERTA_CFG.info;
            const modal = document.getElementById('modal-alerta');

            document.getElementById('modal-alerta-icon-wrap').className = `mx-auto mb-5 w-16 h-16 rounded-full flex items-center justify-center ${c.iconBg}`;
            _setIconoFA('modal-alerta-icon', `fas ${c.icono} text-2xl ${c.iconColor}`);
            document.getElementById('modal-alerta-title').textContent = c.titulo;
            document.getElementById('modal-alerta-msg').textContent = message;

            const progreso = document.getElementById('modal-alerta-progress');
            progreso.className = `h-full rounded-full ${c.barra}`;
            progreso.style.transition = 'none';
            progreso.style.width = '100%';

            modal.classList.remove('hidden');
            void progreso.offsetWidth; // fuerza reflow para que la transición de abajo sí anime
            progreso.style.transition = `width ${duracion}ms linear`;
            progreso.style.width = '0%';

            clearTimeout(_alertaTimer);
            _alertaTimer = setTimeout(cerrarModalAlerta, duracion);
        }

        function cerrarModalAlerta() {
            clearTimeout(_alertaTimer);
            document.getElementById('modal-alerta').classList.add('hidden');
            setTimeout(_procesarColaAlerta, 150); // deja terminar la salida antes de la próxima
        }

        // Reemplaza el alert() nativo del navegador por la alerta centrada.
        // Detecta el tipo según el contenido: ✅ → éxito, ⚠️ → atención,
        // ℹ️ → información; cualquier otro se muestra como error.
        window.alert = function(msg) {
            const s = String(msg);
            let type = 'error';
            if (s.includes('✅')) type = 'success';
            else if (s.includes('⚠')) type = 'warning';
            else if (s.includes('ℹ')) type = 'info';
            showAlerta(type, s);
        };
    </script>

    <script>
        // ===== Control de inactividad =====
        const INAC_LIMITE   = 30 * 60 * 1000;   // 30 minutos total
        const INAC_AVISO    = 29 * 60 * 1000;   // Aviso al minuto 29 (1 min de cuenta regresiva)
        const INAC_COUNTDOWN = 60;               // segundos de cuenta regresiva

        let inacTimer       = null;   // temporizador principal
        let inacInterval    = null;   // intervalo de la cuenta regresiva
        let inacSegundos    = INAC_COUNTDOWN;
        let modalAbierto    = false;

        function resetInactividad() {
            if (modalAbierto) return;  // no resetear si el aviso ya está visible
            clearTimeout(inacTimer);
            inacTimer = setTimeout(mostrarAvisoInactividad, INAC_AVISO);
        }

        function mostrarAvisoInactividad() {
            modalAbierto   = true;
            inacSegundos   = INAC_COUNTDOWN;
            const modal    = document.getElementById('modal-inactividad');
            const display  = document.getElementById('inac-countdown');
            const ring     = document.getElementById('inac-ring');
            const circum   = 2 * Math.PI * 36;   // ≈ 226

            modal.classList.remove('hidden');
            display.textContent = inacSegundos;
            ring.style.strokeDashoffset = 0;

            inacInterval = setInterval(() => {
                inacSegundos--;
                display.textContent = inacSegundos;
                // Anillo se vacía progresivamente
                ring.style.strokeDashoffset = circum * (1 - inacSegundos / INAC_COUNTDOWN);

                if (inacSegundos <= 0) {
                    clearInterval(inacInterval);
                    ejecutarCierreSesionCorporativo();
                }
            }, 1000);
        }

        function renovarSesion() {
            clearInterval(inacInterval);
            document.getElementById('modal-inactividad').classList.add('hidden');
            modalAbierto = false;
            resetInactividad();          // reinicia el temporizador desde cero
            showAlerta('success', 'Sesión renovada. Tienes 30 minutos más de actividad.');
        }

        // Eventos que cuentan como actividad del usuario
        ['mousemove','mousedown','keydown','scroll','touchstart','click'].forEach(ev =>
            document.addEventListener(ev, resetInactividad, { passive: true })
        );

        // Arrancar al cargar
        resetInactividad();
    </script>

    <script>
        // ===== Modal de confirmación personalizado =====
        let _formPendiente = null;

        // "opciones" permite reutilizar el mismo modal para acciones que NO son
        // un borrado real (ej. "dar de baja" un lugar, que es reversible),
        // cambiando el título y el texto/ícono del botón. Si no se pasa nada,
        // se comporta igual que antes (eliminación definitiva).
        function confirmarEliminar(form, mensaje, opciones) {
            opciones = opciones || {};
            _formPendiente = form;
            document.getElementById('modal-confirmar-msg').textContent = mensaje || 'Esta acción no se puede deshacer.';
            document.getElementById('modal-confirmar-title').textContent = opciones.titulo || '¿Confirmar eliminación?';
            document.getElementById('modal-confirmar-btn-texto').textContent = opciones.boton || 'Eliminar';
            _setIconoFA('modal-confirmar-btn-icon', 'fas ' + (opciones.icono || 'fa-trash-alt') + ' text-xs');
            document.getElementById('modal-confirmar').classList.remove('hidden');
            document.getElementById('modal-confirmar-btn').onclick = function() {
                if (_formPendiente) _formPendiente.submit();
            };
            return false; // evita el submit nativo
        }

        function cerrarModalConfirmar() {
            _formPendiente = null;
            document.getElementById('modal-confirmar').classList.add('hidden');
        }

        // "confirmarAccion" es la versión de confirmarEliminar para acciones
        // que NO son un submit de formulario (ej. quitar una imagen ya
        // subida antes de guardar el resto del formulario): en vez de enviar
        // un form, ejecuta un callback si el usuario confirma. Comparte el
        // mismo modal visual — un solo diseño de confirmación en todo el panel.
        function confirmarAccion(mensaje, onConfirm, opciones) {
            opciones = opciones || {};
            _formPendiente = null;
            document.getElementById('modal-confirmar-msg').textContent = mensaje || '¿Deseas continuar?';
            document.getElementById('modal-confirmar-title').textContent = opciones.titulo || '¿Confirmar acción?';
            document.getElementById('modal-confirmar-btn-texto').textContent = opciones.boton || 'Confirmar';
            _setIconoFA('modal-confirmar-btn-icon', 'fas ' + (opciones.icono || 'fa-check') + ' text-xs');
            document.getElementById('modal-confirmar').classList.remove('hidden');
            document.getElementById('modal-confirmar-btn').onclick = function() {
                cerrarModalConfirmar();
                onConfirm();
            };
        }

        // Cerrar con Escape (confirmación y alerta)
        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            cerrarModalConfirmar();
            if (!document.getElementById('modal-alerta').classList.contains('hidden')) cerrarModalAlerta();
        });
    </script>

    <script>
        async function ejecutarCierreSesionCorporativo(e) {
            if (e) e.preventDefault();
            const token = localStorage.getItem('token');
            const usuarioRaw = localStorage.getItem('usuario');

            try {
                if (usuarioRaw && token) {
                    await fetch('http://127.0.0.1:3000/api/admin/guardar-progreso', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + token
                        },
                        body: usuarioRaw
                    });
                }
                if (token) {
                    await fetch('http://127.0.0.1:3000/api/logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + token
                        }
                    });
                }
            } catch (error) {
                console.error("Error en la sincronización del logout corporativo:", error);
            } finally {
                localStorage.removeItem('token');
                localStorage.removeItem('usuario');
                window.location.href = 'http://127.0.0.1:3000/login';
            }
        }
    </script>
</body>
</html>