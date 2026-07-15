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
                    colors: {
                        /* Paleta institucional EXACTA del frontend (verde + oro).
                           Reemplaza la paleta green/gold por defecto de Tailwind,
                           que era un verde distinto (más brillante). */
                        green: {
                            50:'#ecfdf3',100:'#d2f9e0',200:'#a8f0c6',300:'#6fe0a6',400:'#38c882',
                            500:'#059c45',600:'#00913f',700:'#00913f',800:'#08573a',900:'#084832',950:'#02281c',
                        },
                        gold: {
                            50:'#fdfaec',100:'#faf1c8',200:'#f5e08d',300:'#efca52',400:'#eab52a',
                            500:'#d99a16',600:'#bd7510',700:'#975211',800:'#7c4115',900:'#693717',950:'#3d1c08',
                        },
                        brand: {
                            green:  '#00913f',
                            emerald:'#059c45',
                            dark:   '#02281c',
                            gold:   '#eab52a',
                        },
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
            /* Paleta de MARCA (verde + oro), unificada con el frontend. */
            --color-navy: #084832;       /* Verde profundo institucional = green-900 del frontend */
            --color-red-accent: #eab52a; /* Oro institucional (acentos de navegación) */
            --color-bg-clean: #f1f5f9;   /* Fondo gris claro nítido para el área de trabajo */
            /* Altura común de la barra superior: la comparten el bloque del logo
               del sidebar y el header de cada página, para que sus líneas
               divisorias queden a la MISMA altura (simétricas). */
            --topbar-h: 116px;
        }

        body {
            font-family: 'Manrope', sans-serif;
            background-color: var(--color-bg-clean);
        }

        /* ── Degradados de MARCA (verde → esmeralda → oro) ── */
        .brand-gradient-bg  { background-image: linear-gradient(120deg, #00913f, #059c45 55%, #eab52a); }
        .brand-gradient-bar { background-image: linear-gradient(90deg, #00913f, #059c45, #eab52a); }
        .brand-gradient-animated {
            background-image: linear-gradient(90deg, #00913f, #059c45, #eab52a, #059c45, #00913f);
            background-size: 200% 100%;
            animation: brand-slide 6s linear infinite;
        }
        @keyframes brand-slide { to { background-position: 200% 50%; } }

        /* Estilos del Sidebar con degradado sutil de marca */
        .sidebar-corporate {
            background-color: var(--color-navy);
            background-image: linear-gradient(180deg, #08573a 0%, #084832 55%, #02281c 100%);
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.15);
        }

        /* Header del panel con la MISMA difuminación (degradado) que el sidebar,
           para que cabecera y menú lateral se lean como un bloque simétrico.
           Ambos arrancan en #08573a arriba, así el borde superior es continuo.
           Altura fija = --topbar-h y contenido centrado en vertical → su línea
           inferior coincide con la del bloque del logo del sidebar. */
        .header-corporate {
            background-color: var(--color-navy);
            background-image: linear-gradient(180deg, #08573a 0%, #084832 55%, #02281c 100%);
            min-height: var(--topbar-h);
            display: flex;
            align-items: center;
        }

        /* Bloque del logo del sidebar: misma altura que el header. */
        .sidebar-brand { min-height: var(--topbar-h); }

        /* Enlaces de navegación con alto contraste y enfoque nítido */
        .nav-link-premium {
            font-weight: 600;
            color: #d7e6dd;
            transition: color .2s ease, background-color .2s ease, border-color .2s ease, padding-left .2s ease;
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
            background-color: rgba(234, 181, 42, 0.14);
            border-left-color: var(--color-red-accent);
            box-shadow: inset 0 0 22px -6px rgba(234, 181, 42, 0.35);
        }
        .nav-link-premium i { transition: transform .2s ease; }
        .nav-link-premium:hover i,
        .nav-link-premium.active i { transform: scale(1.12); color: #f5cf5e; }

        /* Título de cada sección/categoría del menú. Se oculta al colapsar el
           sidebar (clase sidebar-collapsible). */
        .nav-section {
            padding: 0.9rem 1rem 0.35rem;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: rgba(255, 255, 255, 0.38);
            user-select: none;
        }
        /* Al colapsar, una línea divisoria sustituye a los títulos para no
           perder la separación entre secciones. */
        #sidebar.collapsed .nav-section {
            padding: 0.5rem 0;
            margin: 0 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* ── Entrada escalonada de los enlaces del menú al cargar (fresco) ──
           Usa nth-of-type para contar SOLO los <a> (ignora los títulos <p>). */
        @keyframes nav-in { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }
        /* Entrada de los enlaces al abrir el cajón en móvil (deslizan desde la
           izquierda con un pequeño desfase entre uno y otro). */
        @keyframes drawer-link-in { from { opacity: 0; transform: translateX(-14px); } to { opacity: 1; transform: translateX(0); } }
        #sidebar nav > a { animation: nav-in .45s cubic-bezier(.16,1,.3,1) both; }
        #sidebar nav > a:nth-of-type(1) { animation-delay: .04s; }
        #sidebar nav > a:nth-of-type(2) { animation-delay: .09s; }
        #sidebar nav > a:nth-of-type(3) { animation-delay: .14s; }
        #sidebar nav > a:nth-of-type(4) { animation-delay: .19s; }
        #sidebar nav > a:nth-of-type(5) { animation-delay: .24s; }
        #sidebar nav > a:nth-of-type(6) { animation-delay: .29s; }
        #sidebar nav > a:nth-of-type(7) { animation-delay: .34s; }
        #sidebar nav > a:nth-of-type(8) { animation-delay: .39s; }
        @media (prefers-reduced-motion: reduce) { #sidebar nav > a, #sidebar.mobile-open nav > a { animation: none; } }

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
            border: 2px dashed #00913f;
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
            from { opacity: 0; transform: translateY(14px) scale(.995); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .vista-enter { animation: vista-enter 360ms cubic-bezier(0.16, 1, 0.3, 1) both; }
        @media (prefers-reduced-motion: reduce) {
            .vista-enter { animation: none; }
        }

        /* Scroll del listado de enlaces del sidebar SIN barra visible: el
           scroll sigue funcionando (overflow-y-auto en el <nav>), solo se
           oculta el "riel" gris para que no se vea recortado/feo dentro del
           menú de marca. Cubre WebKit/móvil (Chrome, Safari), Firefox e IE/Edge legado. */
        #sidebar nav { scrollbar-width: none; -ms-overflow-style: none; }
        #sidebar nav::-webkit-scrollbar { display: none; width: 0; height: 0; }

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

        /* ===== Barra superior móvil (con botón hamburguesa) ===== */
        /* Mismo degradado de marca que el sidebar/header, pero se mantiene
           pegajosa en móvil (a diferencia de .header-corporate, que abajo se
           vuelve estática para no chocar con esta barra). */
        .mobile-topbar { background-image: linear-gradient(180deg, #08573a 0%, #084832 55%, #02281c 100%); }

        /* ===== Responsive móvil/tablet: sidebar como cajón deslizante ===== */
        /* En escritorio (lg ≥1024px) el menú es una columna fija a la izquierda.
           Por debajo, se convierte en un cajón (off-canvas) oculto que se abre
           con el botón hamburguesa y se cierra tocando el fondo oscuro. */
        @media (max-width: 1023px) {
            #sidebar {
                position: fixed;
                top: 0; bottom: 0; left: 0;
                transform: translateX(-100%);
                /* Entrada con ease-out expresivo (rápido al inicio, frena suave);
                   salida un poco más corta para que cerrar se sienta ágil. */
                transition: transform .34s cubic-bezier(.16, 1, .3, 1);
                z-index: 50;
                box-shadow: 8px 0 40px rgba(0, 0, 0, .4);
                will-change: transform;
            }
            #sidebar.mobile-open { transform: translateX(0); }
            #sidebar:not(.mobile-open) { transition-duration: .26s; }

            /* Los enlaces del menú entran escalonados CADA vez que se abre el
               cajón (el selector .mobile-open re-dispara la animación al añadirse
               la clase). En móvil se anula la animación de carga (nav-in), que
               se reproducía inútilmente con el cajón fuera de pantalla. */
            #sidebar nav > a { animation: none; }
            #sidebar.mobile-open nav > a { animation: drawer-link-in .45s cubic-bezier(.16, 1, .3, 1) both; }
            #sidebar.mobile-open nav > a:nth-of-type(1) { animation-delay: .06s; }
            #sidebar.mobile-open nav > a:nth-of-type(2) { animation-delay: .10s; }
            #sidebar.mobile-open nav > a:nth-of-type(3) { animation-delay: .14s; }
            #sidebar.mobile-open nav > a:nth-of-type(4) { animation-delay: .18s; }
            #sidebar.mobile-open nav > a:nth-of-type(5) { animation-delay: .22s; }
            #sidebar.mobile-open nav > a:nth-of-type(6) { animation-delay: .26s; }
            #sidebar.mobile-open nav > a:nth-of-type(7) { animation-delay: .30s; }
            #sidebar.mobile-open nav > a:nth-of-type(8) { animation-delay: .34s; }
            /* En móvil el cajón siempre va "ancho" (ignora el modo mini de
               escritorio) para que se lean los textos de cada enlace. */
            #sidebar.collapsed { width: 16rem !important; }
            #sidebar.collapsed .sidebar-collapsible { display: flex !important; }
            #sidebar.collapsed .sidebar-mini { display: none !important; }
            #sidebar.collapsed nav a { justify-content: flex-start; padding-left: 1rem !important; }
            #sidebar.collapsed nav a span { display: inline !important; }

            /* Los headers de cada página dejan de ser pegajosos en móvil (para
               no solaparse con la barra hamburguesa) y ocupan menos alto. */
            .header-corporate {
                position: static !important;
                min-height: 0 !important;
                padding-left: 1rem !important;
                padding-right: 1rem !important;
                padding-top: 1.1rem !important;
                padding-bottom: 1.1rem !important;
            }
        }

        /* ===== PULIDO: accesibilidad y microdetalles de marca ===== */
        html { scroll-behavior: smooth; }
        ::selection { background: rgba(234, 181, 42, 0.35); color: #04301e; }

        /* Foco visible por TECLADO (a11y) coherente con la marca; no afecta al clic. */
        a.nav-link-premium:focus-visible,
        button:focus-visible {
            outline: 2px solid #eab52a;
            outline-offset: 2px;
            border-radius: 10px;
        }

        /* Barra de scroll sutil en el área de trabajo (coherente, no chillona) */
        main::-webkit-scrollbar { width: 10px; }
        main::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; border: 2px solid #f1f5f9; }
        main::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Tarjetas contenedoras: transición suave para hover consistente */
        .card-premium-shadow { transition: box-shadow .25s ease, transform .25s ease; }

        /* ===== Respeto a "reducir movimiento" (accesibilidad, obligatorio) =====
           Todo lo animado (incluido el bucle de la barra de marca) colapsa a estático. */
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .brand-gradient-animated { animation: none; }
            .vista-enter,
            #sidebar nav > a { animation: none; }
        }
    </style>
</head>
<body class="antialiased select-none text-slate-950">

    <div class="flex h-screen overflow-hidden">

        {{-- Fondo oscuro del cajón (solo móvil). Siempre en el DOM: se funde con
             opacity/transition en vez de aparecer de golpe. Cuando está cerrado,
             pointer-events-none deja pasar los clics al contenido. --}}
        <div id="sidebar-backdrop" onclick="toggleMobileSidebar()"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden opacity-0 pointer-events-none transition-opacity duration-300 ease-out"></div>

        {{-- Sidebar Izquierdo (columna fija en escritorio; cajón deslizante en móvil) --}}
        <aside id="sidebar" class="relative w-64 sidebar-corporate text-white flex flex-col z-20 shrink-0">

            {{-- Botón flotante para abrir/cerrar el menú (borde derecho, centrado) --}}
            <button type="button" onclick="toggleSidebar()" title="Abrir/cerrar menú" aria-label="Abrir o cerrar menú"
                    class="sidebar-toggle-btn group absolute top-1/2 -right-5 -translate-y-1/2 z-40 w-11 h-11 rounded-full
                           hidden lg:flex items-center justify-center text-white bg-brand-green hover:bg-brand-emerald
                           border-2 border-white shadow-lg transition-all duration-200 active:scale-90 hover:scale-105">
                <i class="fas fa-chevron-left text-base group-hover:scale-110 transition-transform"></i>
            </button>

            <div class="sidebar-brand px-5 border-b border-white/10 flex flex-col items-center justify-center bg-black/10">
                {{-- Logo completo (mismo de marca que el frontend: sello "C" verde→oro) --}}
                <div class="sidebar-collapsible flex items-center gap-2.5">
                    <span class="grid place-items-center w-10 h-10 rounded-xl brand-gradient-bg ring-1 ring-inset ring-brand-gold/50 text-white font-serif font-black text-lg leading-none shadow-lg">C</span>
                    <div class="flex flex-col leading-none">
                        <span class="font-extrabold italic text-white text-xl tracking-tight">SGT</span>
                        <span class="font-extrabold text-brand-gold text-sm tracking-wide leading-tight">CHIMBO</span>
                        <span class="text-[8px] font-semibold tracking-[0.3em] text-green-200/70 mt-1">GESTIÓN TURÍSTICA</span>
                    </div>
                </div>
                {{-- Logo mini (visible solo cuando el menú está cerrado) --}}
                <div class="sidebar-mini hidden items-center justify-center">
                    <span class="grid place-items-center w-10 h-10 rounded-xl brand-gradient-bg ring-1 ring-inset ring-brand-gold/50 text-white font-serif font-black text-lg leading-none shadow-lg">C</span>
                </div>
            </div>
            
            {{-- min-h-0 es necesario para que overflow-y-auto funcione dentro de un
                 flex-col: sin él, un hijo flex-1 se expande para caber TODO su
                 contenido y el scroll nunca se activa (aunque el menú sea más alto
                 que la pantalla). El logo (arriba) y "Cerrar Sesión" (abajo) quedan
                 fijos; solo esta lista de enlaces se desplaza. --}}
            <nav class="flex-1 min-h-0 overflow-y-auto mt-4 space-y-1 px-2">
                {{-- ── Principal (ambos roles) ── --}}
                <p class="nav-section sidebar-collapsible">Principal</p>
                <a href="{{ route('admin.dashboard') }}" title="Panel de Control" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt w-5 text-center text-base"></i>
                    <span>Panel de Control</span>
                </a>

                {{-- Sección del Admin de Turismo --}}
                @if(auth()->user()?->isAdminTurismo())
                {{-- ── Contenido ── --}}
                <p class="nav-section sidebar-collapsible">Contenido</p>
                <a href="{{ route('admin.home.edit') }}" title="Editar Inicio" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.home.*') ? 'active' : '' }}">
                    <i class="fas fa-house w-5 text-center text-base"></i>
                    <span>Editar Inicio</span>
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
                {{-- ── Turismo ── --}}
                <p class="nav-section sidebar-collapsible">Turismo</p>
                <a href="{{ route('admin.lugares.index') }}" title="Lugares Turísticos" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.lugares.*') ? 'active' : '' }}">
                    <i class="fas fa-map-marker-alt w-5 text-center text-base"></i>
                    <span>Lugares Turísticos</span>
                </a>
                <a href="{{ route('admin.categorias.index') }}" title="Categorías" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.categorias.*') ? 'active' : '' }}">
                    <i class="fas fa-tags w-5 text-center text-base"></i>
                    <span>Categorías</span>
                </a>
                {{-- ── Análisis ── --}}
                <p class="nav-section sidebar-collapsible">Análisis</p>
                <a href="{{ route('admin.reportes.index') }}" title="Reportes" class="nav-link-premium flex items-center gap-3 px-4 py-3 rounded-lg text-sm {{ request()->routeIs('admin.reportes.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line w-5 text-center text-base"></i>
                    <span>Reportes</span>
                </a>
                @endif

                {{-- Sección del Administrador --}}
                @if(auth()->user()?->isAdministrador())
                {{-- ── Administración ── --}}
                <p class="nav-section sidebar-collapsible">Administración</p>
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
                    class="w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold uppercase tracking-wider rounded-lg text-xs transition-all duration-150 flex items-center justify-center gap-2 shadow-md shadow-red-900/30 active:scale-[0.98]"
                >
                    <i class="fas fa-sign-out-alt text-sm"></i> <span class="sidebar-collapsible">Cerrar Sesión</span>
                </button>
            </div>
        </aside>

        {{-- Área de Contenido Principal sin paddings forzados --}}
        <main class="flex-1 overflow-y-auto relative z-10 flex flex-col bg-[#f1f5f9]">

            {{-- Barra superior SOLO móvil/tablet con botón hamburguesa.
                 En escritorio (lg+) se oculta: allí el sidebar ya está visible. --}}
            <div class="lg:hidden sticky top-0 z-30 mobile-topbar text-white flex items-center gap-3 px-4 py-3 shadow-lg">
                <button type="button" onclick="toggleMobileSidebar()" aria-label="Abrir menú"
                        class="w-10 h-10 -ml-1 rounded-lg flex items-center justify-center hover:bg-white/10 active:scale-90 transition-all">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <span class="grid place-items-center w-8 h-8 rounded-lg brand-gradient-bg ring-1 ring-inset ring-brand-gold/50 text-white font-serif font-black text-sm leading-none shadow">C</span>
                <span class="font-extrabold italic text-white text-base tracking-tight">SGT <span class="not-italic text-brand-gold">CHIMBO</span></span>
            </div>

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
            <button type="button" onclick="cerrarModalAlerta()" class="px-8 py-2.5 rounded-xl bg-brand-green hover:bg-brand-emerald text-white text-sm font-black transition-all shadow-md">
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
                    class="px-6 py-2.5 rounded-xl bg-brand-green hover:bg-brand-emerald text-white text-sm font-black transition-all shadow-md flex items-center gap-2">
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
            <div class="mx-auto mb-5 w-16 h-16 rounded-full bg-red-50 flex items-center justify-center">
                <i class="fas fa-trash-alt text-2xl text-red-500"></i>
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
                    class="px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-black transition-all shadow-md shadow-red-200 flex items-center gap-2">
                    <i id="modal-confirmar-btn-icon" class="fas fa-trash-alt text-xs"></i>
                    <span id="modal-confirmar-btn-texto">Eliminar</span>
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(.92) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
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

        // ===== Cajón lateral en móvil (off-canvas) =====
        function toggleMobileSidebar() {
            const sb = document.getElementById('sidebar');
            const bd = document.getElementById('sidebar-backdrop');
            const abierto = sb.classList.toggle('mobile-open');
            // Funde el fondo oscuro (opacity + pointer-events) en vez de ocultarlo de golpe
            bd.classList.toggle('opacity-0', !abierto);
            bd.classList.toggle('pointer-events-none', !abierto);
            // Bloquea el scroll del fondo mientras el cajón está abierto
            document.body.style.overflow = abierto ? 'hidden' : '';
        }
        // Al tocar un enlace del menú en móvil, cerrar el cajón automáticamente
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('#sidebar nav a').forEach((a) => {
                a.addEventListener('click', () => {
                    if (window.matchMedia('(max-width: 1023px)').matches
                        && document.getElementById('sidebar').classList.contains('mobile-open')) {
                        toggleMobileSidebar();
                    }
                });
            });
        });
        // Si se agranda la ventana a escritorio, asegurar que el cajón quede cerrado
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                document.getElementById('sidebar')?.classList.remove('mobile-open');
                document.getElementById('sidebar-backdrop')?.classList.add('opacity-0', 'pointer-events-none');
                document.body.style.overflow = '';
            }
        });
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
            success: { icono: 'fa-circle-check',        iconBg: 'bg-green-50', iconColor: 'text-green-500', barra: 'bg-green-500', titulo: 'Éxito' },
            error:   { icono: 'fa-circle-xmark',         iconBg: 'bg-red-50',     iconColor: 'text-red-500',     barra: 'bg-red-500',     titulo: 'Error' },
            warning: { icono: 'fa-triangle-exclamation', iconBg: 'bg-amber-50',   iconColor: 'text-amber-500',   barra: 'bg-amber-500',   titulo: 'Atención' },
            info:    { icono: 'fa-circle-info',          iconBg: 'bg-teal-50',    iconColor: 'text-teal-500',    barra: 'bg-teal-500',    titulo: 'Información' },
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
                    await fetch('/api/admin/guardar-progreso', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + token
                        },
                        body: usuarioRaw
                    });
                }
                // Revoca el token de Sanctum del SPA, si lo hubiera.
                if (token) {
                    await fetch('/api/logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + token
                        }
                    });
                }
            } catch (error) {
                console.error("Error sincronizando el cierre de sesión:", error);
            }

            // Limpia las credenciales del SPA guardadas en el navegador.
            localStorage.removeItem('token');
            localStorage.removeItem('usuario');

            // Cierra la SESIÓN WEB del panel con un POST a /logout que INCLUYE el
            // token CSRF (por eso ya NO da 419). El servidor invalida la sesión y
            // redirige al login. Antes se llamaba solo a /api/logout (que borra un
            // token Sanctum, no la sesión web) y el redirect manual dejaba la
            // sesión del panel abierta: el logout "no funcionaba".
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/logout';   // relativo: mismo origen que sirve el panel
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }
    </script>

    <script>
        // ===== Latido de presencia de la pestaña =====
        // Cierra la sesión automáticamente cuando se CIERRA LA PESTAÑA del panel,
        // aunque no se pulse "Cerrar sesión" (complementa a expire_on_close, que
        // cubre el cierre de TODO el navegador). Mientras la pestaña vive, manda
        // un "latido" al servidor cada 5 s; al cerrarla dejan de llegar y el
        // servidor cierra la sesión pasada la gracia (config session.tab_heartbeat_grace, 15 s).
        // Recargar (F5/botón) y el botón "Atrás" reanudan el latido al instante,
        // así que NO cierran la sesión.
        (function () {
            const URL_LATIDO = @json(route('admin.latido'));
            const TOKEN      = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const INTERVALO  = 5000; // 5 s — debe ser MENOR que la gracia del servidor (15 s)

            function latir() {
                const fd = new FormData();
                fd.append('_token', TOKEN);
                try {
                    // sendBeacon es fiable (no se cancela al ocultar la pestaña) y no
                    // bloquea el hilo. Si no existe, se usa fetch con keepalive.
                    if (navigator.sendBeacon) {
                        navigator.sendBeacon(URL_LATIDO, fd);
                    } else {
                        fetch(URL_LATIDO, { method: 'POST', body: fd, keepalive: true, credentials: 'same-origin' });
                    }
                } catch (e) { /* silencioso: el latido es best-effort */ }
            }

            latir();                       // uno inmediato al cargar la página
            setInterval(latir, INTERVALO); // y periódico mientras la pestaña vive

            // Al VOLVER a la pestaña (tras estar en segundo plano, donde el navegador
            // frena los timers), late enseguida para revivir la sesión ANTES de que
            // el usuario navegue: así una pestaña en segundo plano nunca cierra sesión
            // por error, y solo el cierre real de la pestaña la termina.
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') latir();
            });
        })();
    </script>
</body>
</html>