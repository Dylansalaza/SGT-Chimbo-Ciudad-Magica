@extends('admin.reportes.pdf.layout')

@section('titulo', 'Reporte de Visitas')
@section('subtitulo', 'Estadísticas de tráfico del portal turístico de San José de Chimbo')
@section('codigo', 'SGT-REP-VIS')
@section('periodo', $periodo)

@section('contenido')

    {{-- Indicadores --}}
    <table class="kpis">
        <tr>
            <td><div class="kpi-n">{{ number_format($totales['historico']) }}</div><div class="kpi-l">Visitas totales</div></td>
            <td><div class="kpi-n alt">{{ number_format($totales['unicos']) }}</div><div class="kpi-l">Visitantes únicos</div></td>
            <td><div class="kpi-n">{{ number_format($totales['mes']) }}</div><div class="kpi-l">Este mes</div></td>
            <td><div class="kpi-n gold">{{ number_format($totales['hoy']) }}</div><div class="kpi-l">Hoy</div></td>
        </tr>
    </table>

    {{-- Resumen mensual con barra proporcional --}}
    <h2 class="sec">Visitas por mes <span>· últimos 12 meses</span></h2>
    <table class="data">
        <thead>
            <tr><th style="width:32%">Mes</th><th>Distribución</th><th class="num" style="width:16%">Visitas</th></tr>
        </thead>
        <tbody>
            @foreach($mensual as $m)
                @php $pct = $maxMes > 0 ? max(2, round($m['visitas'] * 100 / $maxMes)) : 0; @endphp
                <tr>
                    <td>{{ $m['mes'] }}</td>
                    <td>
                        <div style="background:#e8f3ec; border-radius:4px; height:9px;">
                            <div style="background:#00913f; width:{{ $pct }}%; height:9px; border-radius:4px;"></div>
                        </div>
                    </td>
                    <td class="num">{{ number_format($m['visitas']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="2">Total del periodo (12 meses)</td><td class="num">{{ number_format(collect($mensual)->sum('visitas')) }}</td></tr>
        </tfoot>
    </table>

    {{-- Detalle diario --}}
    <h2 class="sec">Detalle diario <span>· últimos 30 días</span></h2>
    <table class="data">
        <thead>
            <tr><th style="width:22%">Fecha</th><th>Día de la semana</th><th class="num" style="width:16%">Visitas</th></tr>
        </thead>
        <tbody>
            @foreach($diario as $d)
                <tr><td>{{ $d['fecha'] }}</td><td>{{ $d['dia'] }}</td><td class="num">{{ number_format($d['visitas']) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="2">Total (30 días)</td><td class="num">{{ number_format(collect($diario)->sum('visitas')) }}</td></tr>
        </tfoot>
    </table>

@endsection
