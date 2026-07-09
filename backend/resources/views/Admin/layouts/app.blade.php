<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - Sistema de Gestión Turística</title>
    
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
                <div class="sidebar-collapsible flex flex-col items-center">
                    <div class="flex flex-col items-center leading-none mb-1">
                        <span class="text-white text-[36px] font-black italic tracking-tight drop-shadow-md leading-none">SGT</span>
                        <span class="text-[#e11d48] text-[18px] font-black tracking-wider drop-shadow-md mt-0.5">CHIMBO</span>
                    </div>
                    <p class="text-[8.5px] text-slate-300 font-sans tracking-[0.3em] uppercase text-center font-bold opacity-70">
                        Gestión Turística
                    </p>
                </div>
                {{-- Logo mini (visible solo cuando el menú está cerrado) --}}
                <div class="sidebar-mini hidden items-center justify-center">
                    <span class="text-white text-xl font-black italic tracking-tighter">SGT</span>
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
                    class="w-full px-4 py-2.5 bg-[#e11d48] hover:bg-rose-700 text-white font-bold uppercase tracking-wider rounded-lg text-xs transition-all duration-150 flex items-center justify-center gap-2 shadow-md shadow-rose-900/30 active:scale-[0.98]"
                >
                    <i class="fas fa-sign-out-alt text-sm"></i> <span class="sidebar-collapsible">Cerrar Sesión</span>
                </button>
            </div>
        </aside>

        {{-- Área de Contenido Principal sin paddings forzados --}}
        <main class="flex-1 overflow-y-auto relative z-10 flex flex-col bg-[#f1f5f9]">
            @yield('content')
        </main>
        
    </div>
    
    @stack('scripts')

    {{-- ===== Sistema de Toast Notifications global ===== --}}
    <div id="toast-container" class="fixed top-6 right-6 z-[1000] flex flex-col gap-3 pointer-events-none" style="min-width:320px;max-width:400px"></div>

    @if(session('success'))
    <script>document.addEventListener('DOMContentLoaded',()=>showToast('success',@json(session('success'))));</script>
    @endif
    @if(session('error'))
    <script>document.addEventListener('DOMContentLoaded',()=>showToast('error',@json(session('error'))));</script>
    @endif
    @if(session('warning'))
    <script>document.addEventListener('DOMContentLoaded',()=>showToast('warning',@json(session('warning'))));</script>
    @endif
    @if(session('info'))
    <script>document.addEventListener('DOMContentLoaded',()=>showToast('info',@json(session('info'))));</script>
    @endif
    @if($errors->any())
    <script>document.addEventListener('DOMContentLoaded',()=>showToast('error',@json($errors->first())));</script>
    @endif

    <style>
        @keyframes toastIn  { from { opacity:0; transform:translateX(60px) scale(.95); } to { opacity:1; transform:translateX(0) scale(1); } }
        @keyframes toastOut { from { opacity:1; transform:translateX(0) scale(1); } to { opacity:0; transform:translateX(60px) scale(.95); } }
        .toast-item { animation: toastIn .3s cubic-bezier(.22,1,.36,1) forwards; }
        .toast-item.hiding { animation: toastOut .3s ease forwards; }
    </style>

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
        // ===== Toast Notifications =====
        function showToast(type, message, duration = 5000) {
            const cfg = {
                success: { bg: 'bg-white', bar: 'bg-emerald-500', icon: 'fa-circle-check', iconCls: 'text-emerald-500', title: 'Éxito' },
                error:   { bg: 'bg-white', bar: 'bg-rose-500',    icon: 'fa-circle-xmark', iconCls: 'text-rose-500',    title: 'Error' },
                warning: { bg: 'bg-white', bar: 'bg-amber-400',   icon: 'fa-triangle-exclamation', iconCls: 'text-amber-500', title: 'Atención' },
                info:    { bg: 'bg-white', bar: 'bg-blue-500',    icon: 'fa-circle-info', iconCls: 'text-blue-500',    title: 'Información' },
            };
            const c = cfg[type] || cfg.info;
            const container = document.getElementById('toast-container');

            const el = document.createElement('div');
            el.className = `toast-item pointer-events-auto ${c.bg} rounded-2xl shadow-2xl border border-gray-100 overflow-hidden flex items-stretch`;
            el.style.cssText = 'min-width:300px;max-width:400px';
            el.innerHTML = `
                <div class="w-1.5 shrink-0 ${c.bar} rounded-l-2xl"></div>
                <div class="flex items-start gap-3 px-4 py-4 flex-1">
                    <i class="fas ${c.icon} ${c.iconCls} text-xl mt-0.5 shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-black text-slate-800">${c.title}</p>
                        <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">${message}</p>
                    </div>
                    <button onclick="dismissToast(this.closest('.toast-item'))" class="text-slate-300 hover:text-slate-500 transition ml-1 shrink-0 mt-0.5">
                        <i class="fas fa-xmark text-sm"></i>
                    </button>
                </div>`;
            container.appendChild(el);

            // Barra de progreso temporal
            const bar = el.querySelector('.' + c.bar.replace('bg-','').split('-').map((v,i)=>i===0?v:v).join('-'));
            setTimeout(() => dismissToast(el), duration);
        }

        function dismissToast(el) {
            if (!el || el.classList.contains('hiding')) return;
            el.classList.add('hiding');
            el.addEventListener('animationend', () => el.remove(), { once: true });
        }

        // Reemplaza el alert() nativo del navegador por toasts elegantes.
        // Detecta el tipo según el contenido: ✅ → éxito, ⚠️ → atención,
        // ℹ️ → información; cualquier otro se muestra como error.
        window.alert = function(msg) {
            const s = String(msg);
            let type = 'error';
            if (s.includes('✅')) type = 'success';
            else if (s.includes('⚠')) type = 'warning';
            else if (s.includes('ℹ')) type = 'info';
            showToast(type, s.replace(/^[✅⚠️ℹ️\s]+/, ''));
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
            showToast('success', 'Sesión renovada. Tienes 30 minutos más de actividad.');
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
            document.getElementById('modal-confirmar-btn-icon').className = 'fas ' + (opciones.icono || 'fa-trash-alt') + ' text-xs';
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

        // Cerrar con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') cerrarModalConfirmar();
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