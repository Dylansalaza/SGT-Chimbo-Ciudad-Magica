<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña — SGT Chimbo</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js" crossorigin="anonymous"></script>
    <style>body{font-family:'Manrope',sans-serif;}</style>
</head>
<body class="min-h-screen flex items-center justify-center bg-[#00294d] p-4">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8">

        {{-- Logo --}}
        <div class="flex flex-col items-center mb-6">
            <span class="text-[#00294d] text-[40px] font-black italic tracking-tight leading-none">SGT</span>
            <span class="text-[#e11d48] text-[20px] font-black tracking-wider">CHIMBO</span>
            <p class="text-[9px] text-slate-400 tracking-[0.3em] uppercase font-bold mt-1">Gestión Turística</p>
        </div>

        <h1 class="font-serif text-xl font-extrabold text-slate-900 mb-1">Recuperar contraseña</h1>
        <p class="text-sm text-slate-500 mb-6">Escribe tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>

        {{-- ✅ Enlace generado + correo enviado --}}
        @if(session('reset_link'))
            @if(session('reset_sent'))
                <div class="bg-emerald-50 border border-emerald-300 text-emerald-900 px-4 py-4 rounded-xl mb-5 text-sm">
                    <p class="font-bold text-base mb-1 flex items-center gap-1.5"><i class="fas fa-envelope"></i> Correo enviado</p>
                    <p class="text-emerald-700 mb-1">
                        Enviamos el enlace de recuperación a <strong>{{ session('reset_email') }}</strong>.
                        Revisa tu bandeja de entrada y también la carpeta de <strong>spam o correo no deseado</strong>.
                    </p>
                    <p class="text-emerald-600 text-xs mt-2">El enlace vence en 60 minutos.</p>
                    <hr class="border-emerald-200 my-3">
                    <p class="text-xs text-emerald-700 mb-2">¿No llegó? Usa este botón directamente:</p>
                    <a href="{{ session('reset_link') }}"
                       class="inline-block w-full text-center bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-lg transition text-sm">
                        Restablecer mi contraseña <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            @else
                {{-- Correo no salió (SMTP no configurado), mostramos el enlace directo --}}
                <div class="bg-amber-50 border border-amber-300 text-amber-900 px-4 py-4 rounded-xl mb-5 text-sm">
                    <p class="font-bold text-base mb-1 flex items-center gap-1.5"><i class="fas fa-link"></i> Enlace generado</p>
                    <p class="text-amber-800 mb-3">
                        El correo automático no pudo enviarse (revisa la configuración SMTP en <code>.env</code>).
                        Usa este botón para restablecer la contraseña de <strong>{{ session('reset_email') }}</strong>:
                    </p>
                    <a href="{{ session('reset_link') }}"
                       class="inline-block w-full text-center bg-amber-600 hover:bg-amber-700 text-white font-bold px-4 py-2.5 rounded-lg transition text-sm">
                        Restablecer mi contraseña <i class="fas fa-arrow-right"></i>
                    </a>
                    <p class="text-xs text-amber-600 mt-2">El enlace vence en 60 minutos.</p>
                </div>
            @endif
        @endif

        @if(session('error'))
            <div class="bg-rose-50 border border-rose-300 text-rose-800 px-4 py-3 rounded-xl mb-4 text-sm font-semibold flex items-center gap-1.5">
                <i class="fas fa-triangle-exclamation"></i> {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="bg-rose-50 border border-rose-300 text-rose-800 px-4 py-3 rounded-xl mb-4 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1.5">Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       placeholder="tucorreo@ejemplo.com"
                       class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-300 focus:border-[#00294d] focus:ring-2 focus:ring-[#00294d]/20 outline-none text-sm transition">
            </div>
            <button type="submit"
                    class="w-full bg-[#00294d] hover:bg-[#003d73] text-white font-extrabold uppercase tracking-wider px-6 py-3 rounded-xl text-sm transition shadow-md">
                Enviar enlace de recuperación
            </button>
        </form>

        <a href="{{ route('login') }}" class="block text-center text-sm text-slate-400 hover:text-slate-600 mt-6 transition">
            <i class="fas fa-arrow-left"></i> Volver a iniciar sesión
        </a>
    </div>

</body>
</html>
