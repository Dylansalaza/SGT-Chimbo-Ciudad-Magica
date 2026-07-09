<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Visitas - SGT Chimbo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js" crossorigin="anonymous"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .page { box-shadow: none !important; margin: 0 !important; }
        }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f1f5f9; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 10px; font-size: 12px; text-align: left; }
        th { background: #00294d; color: white; }
        tr:nth-child(even) td { background: #f8fafc; }
    </style>
</head>
<body class="py-6">

    {{-- Barra de acciones (no se imprime) --}}
    <div class="no-print max-w-4xl mx-auto mb-4 flex items-center justify-between px-4">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900 inline-flex items-center gap-1.5"><i class="fas fa-arrow-left"></i> Volver al panel</a>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.reportes.visitas.csv') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-lg text-sm shadow inline-flex items-center gap-2">
                <i class="fas fa-file-csv"></i> Exportar CSV (Excel)
            </a>
            <button onclick="window.print()" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-5 py-2.5 rounded-lg text-sm shadow inline-flex items-center gap-2">
                <i class="fas fa-print"></i> Descargar / Imprimir PDF
            </button>
        </div>
    </div>

    <div class="page max-w-4xl mx-auto bg-white shadow-lg rounded-lg p-10">

        {{-- Encabezado --}}
        <div class="flex items-center justify-between border-b-2 border-[#00294d] pb-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-[#00294d]">Reporte de Visitas</h1>
                <p class="text-sm text-slate-500">Sistema de Gestión Turística · San José de Chimbo</p>
            </div>
            <div class="text-right text-xs text-slate-500">
                <p>Generado el</p>
                <p class="font-bold text-slate-700">{{ $generado }}</p>
            </div>
        </div>

        {{-- Tarjetas de totales --}}
        <div class="grid grid-cols-4 gap-3 mb-8">
            <div class="border border-slate-200 rounded-lg p-4 text-center">
                <p class="text-2xl font-black text-[#00294d]">{{ number_format($totales['historico']) }}</p>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 mt-1">Visitas totales</p>
            </div>
            <div class="border border-slate-200 rounded-lg p-4 text-center">
                <p class="text-2xl font-black text-emerald-600">{{ number_format($totales['unicos']) }}</p>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 mt-1">Visitantes únicos</p>
            </div>
            <div class="border border-slate-200 rounded-lg p-4 text-center">
                <p class="text-2xl font-black text-blue-600">{{ number_format($totales['mes']) }}</p>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 mt-1">Este mes</p>
            </div>
            <div class="border border-slate-200 rounded-lg p-4 text-center">
                <p class="text-2xl font-black text-rose-600">{{ number_format($totales['hoy']) }}</p>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 mt-1">Hoy</p>
            </div>
        </div>

        {{-- Resumen mensual --}}
        <h2 class="text-lg font-bold text-slate-800 mb-3">Visitas por mes (últimos 12 meses)</h2>
        <table class="mb-8">
            <thead><tr><th>Mes</th><th class="text-right">Visitas</th></tr></thead>
            <tbody>
                @foreach($mensual as $m)
                    <tr><td>{{ $m['mes'] }}</td><td style="text-align:right">{{ number_format($m['visitas']) }}</td></tr>
                @endforeach
            </tbody>
        </table>

        {{-- Detalle diario --}}
        <h2 class="text-lg font-bold text-slate-800 mb-3">Visitas por día (últimos 30 días)</h2>
        <table>
            <thead><tr><th>Fecha</th><th>Día</th><th class="text-right">Visitas</th></tr></thead>
            <tbody>
                @foreach($diario as $d)
                    <tr><td>{{ $d['fecha'] }}</td><td>{{ $d['dia'] }}</td><td style="text-align:right">{{ number_format($d['visitas']) }}</td></tr>
                @endforeach
            </tbody>
        </table>

        <p class="text-[11px] text-slate-400 mt-8 text-center border-t border-slate-100 pt-4">
            © {{ date('Y') }} Municipio de San José de Chimbo — Reporte generado automáticamente por el Sistema de Gestión Turística.
        </p>
    </div>

</body>
</html>
