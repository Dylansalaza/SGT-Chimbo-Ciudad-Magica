@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">

    {{-- Header --}}
    <div class="sticky top-0 z-50 header-corporate text-white w-full px-10 shadow-lg border-b border-white/5">
        <div class="w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
            <div class="space-y-1">
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl">Gestión de Usuarios</h1>
                <p class="text-sm text-slate-300 font-medium">Crea cuentas, asigna o retira el rol de administrador y elimina usuarios.</p>
            </div>
            <a href="{{ route('admin.usuarios.create') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl text-xs font-black tracking-wider shadow-md transition-all uppercase self-start sm:self-center">
                <i class="fas fa-user-plus"></i> Nuevo Usuario
            </a>
        </div>
    </div>

    <div class="p-4 sm:p-6 lg:p-8 w-full">
        <div class="bg-white rounded-2xl p-6 card-premium-shadow w-full">

            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/75 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider w-20 text-center">ID</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider">Correo</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider">Correo de recuperación</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider text-center w-32">Rol</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider text-center w-64">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($usuarios as $u)
                        <tr class="hover:bg-slate-50/40 transition-colors">
                            <td class="px-6 py-4 text-sm text-slate-400 font-bold text-center">{{ $u->id }}</td>
                            <td class="px-6 py-4 text-sm font-extrabold text-slate-900">{{ $u->name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600 font-medium">{{ $u->email }}</td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.usuarios.recovery', $u->id) }}" class="flex items-center gap-1.5">
                                    @csrf
                                    @method('PATCH')
                                    <input type="email" name="recovery_email" value="{{ $u->recovery_email }}" placeholder="agregar correo…"
                                           class="px-2 py-1 rounded-lg border border-slate-200 text-xs w-48 focus:border-[#00913f] outline-none">
                                    <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-green-50 text-green-600 hover:bg-green-100 border border-green-100/70">
                                        <i class="fas fa-save text-[10px]"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($u->rol === 'administrador')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-amber-50 text-amber-700 border border-amber-100">
                                        <i class="fas fa-shield-alt text-[9px]"></i> Administrador
                                    </span>
                                @elseif($u->rol === 'admin_turismo')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-teal-50 text-teal-700 border border-teal-100">
                                        <i class="fas fa-map-marked-alt text-[9px]"></i> Admin Turismo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-50 text-slate-500 border border-slate-100">
                                        Usuario
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Cambiar rol --}}
                                    <form method="POST" action="{{ route('admin.usuarios.toggleAdmin', $u->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        @if($u->rol === 'administrador')
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-600 hover:bg-amber-100 border border-amber-100/70 transition">
                                                <i class="fas fa-user-shield text-[10px]"></i> Quitar admin
                                            </button>
                                        @elseif($u->rol === 'admin_turismo')
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-600 hover:bg-amber-100 border border-amber-100/70 transition">
                                                <i class="fas fa-user-shield text-[10px]"></i> Quitar admin
                                            </button>
                                        @else
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-600 hover:bg-amber-100 border border-amber-100/70 transition">
                                                <i class="fas fa-user-shield text-[10px]"></i> Hacer admin
                                            </button>
                                        @endif
                                    </form>
                                    <form method="POST" action="{{ route('admin.usuarios.destroy', $u->id) }}" onsubmit="return confirmarEliminar(this, '¿Seguro que deseas eliminar al usuario «' + '{{ addslashes($u->name) }}' + '»? Esta acción no se puede deshacer.')"  >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-red-50 text-red-600 hover:bg-red-100 border border-red-100/70 transition">
                                            <i class="fas fa-trash-alt text-[10px]"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400 text-sm">No hay usuarios registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
