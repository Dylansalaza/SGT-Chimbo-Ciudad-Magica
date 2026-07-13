@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    {{-- Header --}}
    <div class="sticky top-0 z-50 header-corporate text-white w-full px-10 shadow-lg border-b border-white/5">
        <div class="w-full flex items-center justify-between gap-6">
            <div class="space-y-1">
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl">Nuevo Usuario</h1>
                <p class="text-sm text-slate-300 font-medium">Crea una cuenta y decide si tendrá privilegios de administrador.</p>
            </div>
            <a href="{{ route('admin.usuarios.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="p-4 sm:p-6 lg:p-8 w-full">
        <div class="bg-white rounded-2xl p-8 card-premium-shadow max-w-2xl mx-auto">



            <form method="POST" action="{{ route('admin.usuarios.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Nombre completo</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00913f] focus:ring-2 focus:ring-[#00913f]/20 outline-none text-sm">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Correo electrónico</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00913f] focus:ring-2 focus:ring-[#00913f]/20 outline-none text-sm">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5 flex items-center gap-1.5"><i class="fas fa-envelope text-slate-400"></i> Correo de recuperación <span class="text-slate-400 font-normal">(opcional)</span></label>
                    <input type="email" name="recovery_email" value="{{ old('recovery_email') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00913f] focus:ring-2 focus:ring-[#00913f]/20 outline-none text-sm"
                           placeholder="correo alterno para recuperar la contraseña">
                    <p class="text-xs text-slate-400 mt-1">Servirá para recuperar la contraseña si olvida la principal.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Contraseña</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00913f] focus:ring-2 focus:ring-[#00913f]/20 outline-none text-sm">
                        <p class="text-xs text-slate-400 mt-1">Mínimo 8 caracteres.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00913f] focus:ring-2 focus:ring-[#00913f]/20 outline-none text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Rol del usuario *</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                        <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition
                            {{ old('rol') === 'administrador' ? 'border-[#00913f] bg-[#00913f]/5' : 'border-slate-200 hover:border-slate-300' }}">
                            <input type="radio" name="rol" value="administrador" required
                                   {{ old('rol') === 'administrador' ? 'checked' : '' }}
                                   class="mt-0.5 text-[#00913f] focus:ring-[#00913f]/30">
                            <span>
                                <span class="block text-sm font-bold text-slate-800">
                                    <i class="fas fa-user-shield text-[#00913f] mr-1"></i> Administrador
                                </span>
                                <span class="block text-xs text-slate-500 mt-0.5">
                                    Solo puede ver el Dashboard y gestionar usuarios del sistema.
                                </span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition
                            {{ old('rol') === 'admin_turismo' ? 'border-green-600 bg-green-50' : 'border-slate-200 hover:border-slate-300' }}">
                            <input type="radio" name="rol" value="admin_turismo" required
                                   {{ old('rol') === 'admin_turismo' ? 'checked' : '' }}
                                   class="mt-0.5 text-green-600 focus:ring-green-500/30">
                            <span>
                                <span class="block text-sm font-bold text-slate-800">
                                    <i class="fas fa-map-marked-alt text-green-600 mr-1"></i> Administrador del Proyecto de Investigación
                                </span>
                                <span class="block text-xs text-slate-500 mt-0.5">
                                    Gestiona eventos, noticias, galerías, lugares, categorías y reportes.
                                </span>
                            </span>
                        </label>

                    </div>
                </div>

                <div class="pt-2 flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl text-xs font-black tracking-wider shadow-md transition uppercase">
                        <i class="fas fa-save"></i> Crear Usuario
                    </button>
                    <a href="{{ route('admin.usuarios.index') }}" class="px-6 py-3 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 uppercase tracking-wider">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
