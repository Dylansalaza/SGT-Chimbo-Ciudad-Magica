<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña — SGT Chimbo</title>
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
                        sans: ['Manrope', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                    },
                    colors: {
                        green: {
                            50:'#e8f9ee',100:'#c6f0d4',200:'#90e2ae',300:'#4fce82',400:'#18b25b',
                            500:'#059c45',600:'#00913f',700:'#04752f',800:'#095c28',900:'#0b4b24',950:'#022c13',
                        },
                        gold: {
                            50:'#fdfaec',100:'#faf1c8',200:'#f5e08d',300:'#efca52',400:'#eab52a',
                            500:'#d99a16',600:'#bd7510',700:'#975211',800:'#7c4115',900:'#693717',950:'#3d1c08',
                        },
                    },
                    boxShadow: {
                        'green-glow': '0 8px 30px -8px rgba(0, 145, 63, 0.45)',
                    },
                },
            },
        };
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js" crossorigin="anonymous"></script>
    <style>
        :root { --ease-out-strong: cubic-bezier(0.23, 1, 0.32, 1); }
        body { font-family: 'Manrope', system-ui, -apple-system, 'Segoe UI', sans-serif; }
        h1, h2, h3 { letter-spacing: -0.02em; text-wrap: balance; }

        .app-bg {
            background-image: linear-gradient(120deg, #04371c, #00913f, #0a9e47, #00913f, #04371c);
            background-size: 260% 260%;
            animation: bg-flow 14s ease-in-out infinite;
        }
        @keyframes bg-flow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .brand-accent {
            background-image: linear-gradient(90deg, #00913f, #059c45, #eab52a, #059c45, #00913f);
            background-size: 200% 100%;
            animation: accent-slide 6s linear infinite;
        }
        @keyframes accent-slide {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        .brand-tile { background-image: linear-gradient(120deg, #00913f, #059c45 55%, #eab52a); }

        @keyframes card-in {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to   { opacity: 1; transform: none; }
        }
        .card-in { animation: card-in 460ms var(--ease-out-strong) both; }

        .btn-press { transition: transform 140ms var(--ease-out-strong), background-color 180ms ease, box-shadow 220ms ease; }
        .btn-press:active { transform: scale(0.97); }

        .field { transition: border-color 160ms ease, box-shadow 200ms ease, background-color 160ms ease; }
        .field:focus { border-color: #00913f; box-shadow: 0 0 0 4px rgba(0, 145, 63, 0.16); outline: none; }

        .card-shadow { box-shadow: 0 24px 60px -18px rgba(0, 145, 63, 0.45), 0 10px 28px -14px rgba(2, 44, 19, 0.40); }

        *:focus-visible { outline: 2px solid #059c45; outline-offset: 2px; }

        @media (prefers-reduced-motion: reduce) {
            .app-bg, .brand-accent { animation: none; }
            .card-in { animation: none; }
            .btn-press:active { transform: none; }
        }
    </style>
</head>
<body class="app-bg antialiased min-h-screen flex items-center justify-center p-4">

    <div class="card-in card-shadow w-full max-w-md bg-white rounded-3xl overflow-hidden">
        {{-- Franja de acento verde→oro --}}
        <div class="brand-accent h-1.5 w-full"></div>

        <div class="p-8">
            {{-- Logo --}}
            <div class="flex flex-col items-center mb-6">
                <div class="flex items-center gap-3 mb-1">
                    <span class="brand-tile grid place-items-center w-11 h-11 rounded-xl text-white font-serif font-black text-xl leading-none shadow-lg ring-1 ring-inset ring-white/40">C</span>
                    <div class="flex flex-col leading-none text-left">
                        <span class="text-green-600 text-[26px] font-black italic tracking-tight leading-none">SGT</span>
                        <span class="text-gold-500 text-[15px] font-black tracking-wider">CHIMBO</span>
                    </div>
                </div>
                <p class="text-[9px] text-slate-400 tracking-[0.3em] uppercase font-bold mt-1">Gestión Turística</p>
            </div>

            <h1 class="font-serif text-xl font-extrabold text-slate-900 mb-1">Define tu nueva contraseña</h1>
            <p class="text-sm text-slate-500 mb-6">Ingresa y confirma tu nueva contraseña.</p>

            @if(session('error'))
                <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-xl mb-4 text-sm font-semibold">
                    {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-xl mb-4 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                {{-- Solo lectura: viene del enlace del correo y es SIEMPRE la cuenta
                     principal, que es contra la que el token fue emitido. Si se
                     pudiera editar (p. ej. escribiendo el correo de recuperación),
                     la validación del token fallaría con "usuario no encontrado". --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Correo electrónico</label>
                    <input type="email" name="email" value="{{ old('email', $email) }}" required readonly
                           class="field w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 text-sm text-slate-500 cursor-not-allowed outline-none">
                    <p class="text-xs text-slate-400 mt-1">Es la cuenta a la que pertenece este enlace.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Nueva contraseña</label>
                    <input type="password" name="password" required
                           class="field w-full px-4 py-2.5 rounded-xl border-2 border-slate-300 text-sm text-slate-900 outline-none">
                    <p class="text-xs text-slate-400 mt-1">Mínimo 8 caracteres.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" required
                           class="field w-full px-4 py-2.5 rounded-xl border-2 border-slate-300 text-sm text-slate-900 outline-none">
                </div>

                <button type="submit"
                        class="btn-press w-full bg-green-600 hover:bg-green-700 text-white font-extrabold uppercase tracking-wider px-6 py-3 rounded-xl text-sm shadow-green-glow">
                    Guardar contraseña
                </button>
            </form>

            <a href="{{ route('login') }}" class="block text-center text-sm text-slate-400 hover:text-green-600 mt-6 transition-colors">
                <i class="fas fa-arrow-left"></i> Volver a iniciar sesión
            </a>
        </div>
    </div>

</body>
</html>
