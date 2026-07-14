@extends('admin.layouts.app')

@section('content')
<div class="w-full flex flex-col">
    
    {{-- Header de Pantalla Completa (mismo verde profundo del sidebar) --}}
    <div class="sticky top-0 z-50 header-corporate text-white w-full px-10 shadow-lg border-b border-white/5">
        <div class="w-full flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
            <div class="space-y-1">
                <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl">Panel de Control General</h1>
                <p class="text-sm text-slate-300 font-medium">Monitoreo en tiempo real del tráfico, estadísticas generales y estado de los módulos del Sistema de Gestión Turística.</p>
            </div>
            <div class="flex items-center gap-3 self-start sm:self-center">
                {{-- Reportes: solo el Admin de Turismo tiene permiso (rol:admin_turismo).
                     Sin este @if, el botón aparecía también al Administrador del sistema
                     y le daba 403 al pulsarlo. --}}
                @if(auth()->user()?->isAdminTurismo())
                <a href="{{ route('admin.reportes.index') }}" class="inline-flex items-center gap-2 bg-white text-[#00913f] px-5 py-2.5 rounded-xl text-xs font-black tracking-wider shadow-md uppercase hover:bg-slate-100 transition">
                    <i class="fas fa-chart-line"></i> Reportes
                </a>
                @endif
                <div class="inline-flex items-center gap-3 bg-green-600 border border-green-500 text-white px-5 py-2.5 rounded-xl text-xs font-black tracking-wider shadow-md uppercase">
                    <span class="w-2.5 h-2.5 bg-white rounded-full animate-ping"></span>
                    SISTEMA EN LÍNEA
                </div>
            </div>
        </div>
    </div>

    {{-- Cuerpo con Margen de Separación --}}
    <div class="p-4 sm:p-6 lg:p-8 w-full space-y-6">
        
        {{-- Mi Perfil Institucional --}}
        <div class="bg-white rounded-2xl p-6 card-premium-shadow">
            <h2 class="text-sm font-black text-gray-800 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fas fa-user text-slate-400"></i> Mi Perfil Institucional
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 bg-gray-50/75 p-5 rounded-xl border border-gray-100">
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Nombre del Usuario</p>
                    <p class="text-sm font-bold text-gray-800 mt-0.5">{{ Auth::user()->name ?? 'Admin' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Correo Electrónico</p>
                    <p class="text-sm font-bold text-gray-800 mt-0.5">{{ Auth::user()->email ?? 'admin@example.test' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Estado de Cuenta</p>
                    <p class="text-sm font-bold text-green-600 mt-0.5 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span> Activo
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Rol Asignado</p>
                    <p class="text-sm font-black text-green-600 mt-0.5 uppercase tracking-wide">
                        {{ Auth::user()->role ?? 'ADMINISTRADOR' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Tarjetas de Estadísticas --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div class="bg-white rounded-2xl p-6 card-premium-shadow flex items-center justify-between">
                <div class="space-y-1">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Visitas Totales</p>
                    <h3 class="text-3xl font-black text-gray-800 tracking-tight">
                        {{ number_format($totalVisits ?? 10) }}
                    </h3>
                    <p class="text-xs text-gray-500 font-medium"><i class="fas fa-chart-column"></i> Tráfico Histórico</p>
                </div>
                <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 text-base border border-gray-100">
                    <i class="fas fa-eye"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 card-premium-shadow flex items-center justify-between">
                <div class="space-y-1">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Visitas del Mes</p>
                    <h3 class="text-3xl font-black text-green-600 tracking-tight">
                        {{ number_format($monthVisits ?? 0) }}
                    </h3>
                    <p class="text-xs text-green-600 font-medium"><i class="fas fa-calendar-days"></i> Tráfico Mensual</p>
                </div>
                <div class="w-12 h-12 bg-green-50/50 rounded-xl flex items-center justify-center text-green-500 text-base border border-green-100/50">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 card-premium-shadow flex items-center justify-between">
                <div class="space-y-1">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Visitas de Hoy</p>
                    <h3 class="text-3xl font-black text-green-600 tracking-tight">
                        {{ number_format($todayVisits ?? 0) }}
                    </h3>
                    <p class="text-xs text-green-600 font-medium"><i class="fas fa-bolt"></i> Tráfico Diario</p>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center text-green-500 text-base border border-green-100">
                    <i class="fas fa-bolt"></i>
                </div>
            </div>

        </div>

        {{-- Gráfico e Índices Inferiores --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="bg-white rounded-2xl p-6 card-premium-shadow lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-50 pb-3">
                    <h2 class="text-sm font-black text-gray-800 uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-chart-line text-slate-400"></i> Tendencia de Tráfico Semanal
                    </h2>
                    <span class="text-[10px] font-black text-gray-400 bg-gray-50 px-3 py-1 rounded-full border border-gray-100 uppercase">Últimos 7 días</span>
                </div>
                <div class="relative w-full h-[280px]">
                    <canvas id="visitsChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 card-premium-shadow space-y-4">
                <div class="border-b border-gray-50 pb-3">
                    <h2 class="text-sm font-black text-gray-800 uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-folder-open text-slate-400"></i> Resumen de Contenidos
                    </h2>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3.5 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-green-50 border border-green-100 text-green-600 flex items-center justify-center text-xs">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <span class="text-sm font-bold text-gray-700">Eventos</span>
                        </div>
                        <span class="text-xs font-black bg-white border border-gray-100 text-gray-800 px-3 py-1 rounded-md shadow-sm">{{ $totalEventos ?? 3 }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3.5 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-green-50 border border-green-100 text-green-600 flex items-center justify-center text-xs">
                                <i class="fas fa-newspaper"></i>
                            </div>
                            <span class="text-sm font-bold text-gray-700">Noticias</span>
                        </div>
                        <span class="text-xs font-black bg-white border border-gray-100 text-gray-800 px-3 py-1 rounded-md shadow-sm">{{ $totalNoticias ?? 2 }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3.5 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-xs">
                                <i class="fas fa-images"></i>
                            </div>
                            <span class="text-sm font-bold text-gray-700">Galerías</span>
                        </div>
                        <span class="text-xs font-black bg-white border border-gray-100 text-gray-800 px-3 py-1 rounded-md shadow-sm">{{ $totalGalerias ?? 1 }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3.5 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-600/20 text-amber-700 flex items-center justify-center text-xs">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <span class="text-sm font-bold text-gray-700">Lugares Turísticos</span>
                        </div>
                        <span class="text-xs font-black bg-white border border-gray-100 text-gray-800 px-3 py-1 rounded-md shadow-sm">{{ $totalPlaces ?? 8 }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('visitsChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.12)');
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0.00)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($days ?? ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']) !!},
                datasets: [{
                    label: 'Visitas Reales',
                    data: {!! json_encode($visitCounts ?? [1, 3, 2, 5, 4, 8, 10]) !!}, 
                    borderColor: '#10b981', 
                    borderWidth: 2.5,
                    pointBackgroundColor: '#10b981', 
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.3 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11, weight: '600' } } },
                    y: { grid: { color: '#f8fafc' }, ticks: { color: '#94a3b8', font: { size: 11, weight: '600' }, precision: 0 } }
                }
            }
        });
    });
</script>
@endpush