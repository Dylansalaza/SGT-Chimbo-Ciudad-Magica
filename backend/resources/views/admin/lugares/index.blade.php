@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">
    
    {{-- Header de Pantalla Completa --}}
    <div class="sticky top-0 z-50 header-corporate text-white w-full px-10 shadow-lg border-b border-white/5">
        <div class="w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
            <div class="space-y-1">
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl">Lista de Lugares Turísticos</h1>
                <p class="text-sm text-slate-300 font-medium">Panel administrativo para gestionar y supervisar todos los puntos de interés registrados en el sistema.</p>
            </div>
            <a href="{{ route('admin.lugares.create') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl text-xs font-black tracking-wider shadow-md transition-all uppercase self-start sm:self-center">
                <i class="fas fa-plus"></i> Nuevo Lugar
            </a>
        </div>
    </div>

    {{-- Contenedor de la Tabla --}}
    <div class="p-4 sm:p-6 lg:p-8 w-full">
        <div class="bg-white rounded-2xl p-6 card-premium-shadow w-full">

            {{-- Pestañas "Activos" / "Dados de baja": filtran la tabla de abajo
                 sin recargar la página (solo muestran/ocultan filas por JS). --}}
            @php
                $totalActivos = $lugares->where('activo', true)->count();
                $totalBaja    = $lugares->where('activo', false)->count();
            @endphp
            <div class="flex gap-2 mb-5">
                <button type="button" id="tab-activos" onclick="filtrarLugares('activo')"
                    class="lugar-tab-btn px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span> Activos <span class="ml-1 opacity-70">({{ $totalActivos }})</span>
                </button>
                <button type="button" id="tab-baja" onclick="filtrarLugares('baja')"
                    class="lugar-tab-btn px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span> Dados de baja <span class="ml-1 opacity-70">({{ $totalBaja }})</span>
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/75 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider w-20 text-center">ID</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider">Categoría</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider">Coordenadas</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider text-center w-32">Estado</th>
                            <th class="px-6 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-wider text-center w-48">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($lugares as $l)
                        <tr data-estado="{{ $l->activo ? 'activo' : 'baja' }}" class="lugar-fila hover:bg-slate-50/40 transition-colors {{ $l->activo ? '' : 'opacity-60' }}">
                            <td class="px-6 py-4 text-sm text-slate-400 font-bold text-center">{{ $l->id }}</td>
                            <td class="px-6 py-4 text-sm font-extrabold text-slate-900">{{ $l->nombre }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-orange-50 text-orange-600 border border-orange-100 uppercase tracking-wide">
                                    {{ $l->categoria ?? '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-400 font-mono tracking-tight">
                                {{ $l->lat ?? '—' }}, {{ $l->lng ?? '—' }}
                            </td>
                            {{-- Columna Estado: refleja el campo booleano "activo" del modelo --}}
                            <td class="px-6 py-4 text-sm text-center">
                                @if($l->activo)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-green-50 text-green-600 border border-green-100 uppercase tracking-wide"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Activo</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200 uppercase tracking-wide"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> De baja</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.lugares.edit', $l->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-black text-white hover:bg-slate-800 transition">
                                        <i class="fas fa-edit text-[10px]"></i> Editar
                                    </a>
                                    {{-- Mismo endpoint (DELETE) para ambos botones: el controlador
                                         alterna "activo" en vez de borrar. Solo cambia el texto/color
                                         y si se pide confirmación (dar de baja) o no (reactivar). --}}
                                    @if($l->activo)
                                        <form method="POST" action="{{ route('admin.lugares.destroy', $l->id) }}" onsubmit="return confirmarEliminar(this, '¿Seguro que deseas dar de baja el lugar «' + '{{ addslashes($l->nombre) }}' + '»? Dejará de mostrarse al público, pero podrás reactivarlo cuando quieras.', {titulo: '¿Dar de baja este lugar?', boton: 'Dar de baja', icono: 'fa-power-off'})">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-red-50 text-red-600 hover:bg-red-100 border border-red-100/70 transition">
                                                <i class="fas fa-power-off text-[10px]"></i> Dar de baja
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.lugares.destroy', $l->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-green-50 text-green-600 hover:bg-green-100 border border-green-100/70 transition">
                                                <i class="fas fa-check text-[10px]"></i> Activar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($lugares->isEmpty())
                <div class="text-center py-12 text-slate-400 text-sm font-medium">
                    No hay lugares turísticos registrados actualmente.
                </div>
            @endif

            {{-- Mensaje que aparece cuando la pestaña activa no tiene ningún lugar --}}
            <div id="lugares-vacio-tab" class="hidden text-center py-12 text-slate-400 text-sm font-medium"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // ===== Pestañas "Activos" / "Dados de baja" =====
    // Muestra/oculta las filas de la tabla según el atributo data-estado,
    // sin recargar la página. Recuerda la última pestaña vista con localStorage.
    function filtrarLugares(estado) {
        document.querySelectorAll('.lugar-fila').forEach((fila) => {
            fila.style.display = (fila.dataset.estado === estado) ? '' : 'none';
        });

        // Estilos de la pestaña activa vs. inactiva
        const btnActivos = document.getElementById('tab-activos');
        const btnBaja    = document.getElementById('tab-baja');
        const activoCls  = ['bg-[#00913f]', 'text-white', 'shadow-md'];
        const inactivoCls = ['bg-slate-100', 'text-slate-500', 'hover:bg-slate-200'];

        [btnActivos, btnBaja].forEach((btn) => btn.classList.remove(...activoCls, ...inactivoCls));

        const btnSeleccionado = estado === 'activo' ? btnActivos : btnBaja;
        const btnOtro         = estado === 'activo' ? btnBaja : btnActivos;
        btnSeleccionado.classList.add(...activoCls);
        btnOtro.classList.add(...inactivoCls);

        // Mensaje de "no hay lugares" específico de la pestaña
        const visibles = [...document.querySelectorAll('.lugar-fila')].filter((f) => f.dataset.estado === estado);
        const vacio = document.getElementById('lugares-vacio-tab');
        if (visibles.length === 0) {
            vacio.textContent = estado === 'activo'
                ? 'No hay lugares activos por ahora.'
                : 'No hay lugares dados de baja por ahora.';
            vacio.classList.remove('hidden');
        } else {
            vacio.classList.add('hidden');
        }

        localStorage.setItem('lugares_tab', estado);
    }

    // Al cargar la página, restaura la última pestaña vista (por defecto "Activos")
    document.addEventListener('DOMContentLoaded', () => {
        filtrarLugares(localStorage.getItem('lugares_tab') || 'activo');
    });
</script>
@endpush
@endsection