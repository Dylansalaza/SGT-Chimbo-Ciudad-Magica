<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña - SGT</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js" crossorigin="anonymous"></script>
    <style>body{font-family:'Manrope',sans-serif;}</style>
</head>
<body class="min-h-screen flex items-center justify-center bg-[#00294d] p-4">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8">
        <h1 class="font-serif text-2xl font-extrabold text-slate-900 mb-1">Define tu nueva contraseña</h1>
        <p class="text-sm text-slate-500 mb-6">Ingresa y confirma tu nueva contraseña.</p>

        @if(session('error'))
            <div class="bg-rose-50 border border-rose-300 text-rose-800 px-4 py-3 rounded-xl mb-4 text-sm font-semibold">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="bg-rose-50 border border-rose-300 text-rose-800 px-4 py-3 rounded-xl mb-4 text-sm">
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

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1.5">Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required
                       class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-300 focus:border-[#0d6efd] outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1.5">Nueva contraseña</label>
                <input type="password" name="password" required
                       class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-300 focus:border-[#0d6efd] outline-none text-sm">
                <p class="text-xs text-slate-400 mt-1">Mínimo 8 caracteres.</p>
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1.5">Confirmar contraseña</label>
                <input type="password" name="password_confirmation" required
                       class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-300 focus:border-[#0d6efd] outline-none text-sm">
            </div>

            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold uppercase tracking-wider px-6 py-3 rounded-xl text-sm transition shadow-md">
                Guardar contraseña
            </button>
        </form>

        <a href="{{ route('login') }}" class="block text-center text-sm text-slate-500 hover:text-slate-700 mt-6">
            <i class="fas fa-arrow-left"></i> Volver a iniciar sesión
        </a>
    </div>

</body>
</html>
