<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Sistema de Gestión Turística</title>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js" crossorigin="anonymous"></script>
    <style>
        :root {
            /* DIVISIÓN MATEMÁTICA EXACTA AL 50% */
            --split-angle: 102deg;
            --split-pos: 50%; 
            
            --color-left: #00294d;       /* Azul Marino Corporativo */
            --color-right-bg: #e2e8f0;   /* Gris de fondo exterior para resaltar la tarjeta */
            --color-right-card: #ffffff; /* Blanco puro para la sección del formulario */
        }

        body {
            font-family: 'Manrope', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            
            /* Fondo dividido exactamente a la mitad y fijado al monitor */
            background-image: linear-gradient(var(--split-angle), var(--color-left) var(--split-pos), var(--color-right-bg) var(--split-pos));
            background-attachment: fixed;
            background-repeat: no-repeat;
        }

        .login-card-master {
            width: 960px;
            height: 520px;
            border-radius: 20px;
            
            /* El fondo de la tarjeta copia el degradado fijo para un empalme milimétrico */
            background-image: linear-gradient(var(--split-angle), var(--color-left) var(--split-pos), var(--color-right-card) var(--split-pos));
            background-attachment: fixed;
            
            /* MAXIMA DENSIDAD DE SOMBRAS: Efecto flotante ultra marcado */
            box-shadow: 0 45px 95px -10px rgba(0, 0, 0, 0.60), 
                        0 25px 45px -15px rgba(0, 0, 0, 0.50),
                        0 0 60px 15px rgba(0, 0, 0, 0.20);
            
            display: flex;
            overflow: hidden;
            position: relative;
        }

        /* Bordes reforzados y texto oscuro para legibilidad extrema */
        .input-high-contrast {
            border: 2px solid #94a3b8 !important;
            color: #020617 !important;
            font-weight: 600;
        }
        .input-high-contrast:focus {
            border-color: #0d6efd !important;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.2) !important;
        }
    </style>
</head>
<body class="antialiased select-none">

    <div class="login-card-master">
        
        <div class="w-1/2 flex flex-col items-center justify-center relative pr-8">
            
            <div class="flex flex-col items-center justify-center">
                <img src="{{ asset('media/logo/logo-horizontal-dark.svg') }}" alt="SGT Chimbo — Sistema de Gestión Turístico" class="w-64 drop-shadow-md">
            </div>

        </div>

        <div class="w-1/2 flex flex-col justify-center pl-10 pr-16 bg-transparent">
            
            <div class="mb-8">
                <h2 class="font-serif text-[32px] font-extrabold text-slate-950 tracking-tight">
                    Credenciales Institucionales
                </h2>
            </div>
            
            @if(session('error'))
                <div class="bg-red-50 border-2 border-red-300 text-red-900 px-4 py-2.5 rounded-lg mb-4 text-xs flex items-center gap-2 font-bold shadow-sm animate-pulse">
                    <i class="fas fa-triangle-exclamation"></i> {{ session('error') }}
                </div>
            @endif
            @if(session('success'))
                <div class="bg-green-50 border-2 border-green-300 text-green-900 px-4 py-2.5 rounded-lg mb-4 text-xs flex items-center gap-2 font-bold shadow-sm">
                    <i class="fas fa-circle-check"></i> {{ session('success') }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf
                
                <div class="grid grid-cols-[150px_1fr] items-center gap-4">
                    <label class="text-right text-[13px] text-slate-950 font-extrabold uppercase tracking-wide">
                        <span class="text-red-600 font-black">*</span> Correo:
                    </label>
                    <input
                        type="email"
                        name="email"
                        class="input-high-contrast rounded-md px-3 py-2 w-full bg-white text-[13px] shadow-sm transition-all"
                        value="{{ old('email') }}"
                        placeholder="correo@ejemplo.com"
                        required
                        autofocus
                    >
                </div>
                
                <div class="grid grid-cols-[150px_1fr] items-start gap-4 pt-1">
                    <label class="text-right text-[13px] text-slate-950 font-extrabold uppercase tracking-wide pt-2.5">
                        <span class="text-red-600 font-black">*</span> Contraseña:
                    </label>
                    <div class="w-full flex flex-col items-end">
                        <input
                            type="password"
                            name="password"
                            class="input-high-contrast rounded-md px-3 py-2 w-full bg-white text-[13px] shadow-sm font-mono tracking-widest transition-all"
                            required
                        >
                        <a href="{{ route('password.request') }}" class="text-[12px] text-[#0040af] hover:text-blue-900 font-bold mt-2.5 hover:underline">
                            He olvidado mi contraseña
                        </a>
                    </div>
                </div>
                
                <div class="grid grid-cols-[150px_1fr] gap-4 pt-3">
                    <div></div>
                    <div>
                        <button 
                            type="submit" 
                            class="bg-[#0d6efd] hover:bg-[#0b5ed7] text-white font-extrabold uppercase tracking-wider px-10 py-3 rounded-md text-[13px] transition-colors shadow-md shadow-blue-500/20 active:scale-[0.98]"
                        >
                            Acceder
                        </button>
                    </div>
                </div>
            </form>
            

        </div>
    </div>

</body>
</html>