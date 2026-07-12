@extends('admin.reportes.pdf.layout')

@section('titulo', 'Reporte de Lugares Turísticos')
@section('subtitulo', 'Catálogo de atractivos registrados en el sistema')
@section('codigo', 'SGT-REP-LUG')

@section('contenido')

    <table class="kpis">
        <tr>
            <td><div class="kpi-n">{{ number_format($totales['total']) }}</div><div class="kpi-l">Lugares registrados</div></td>
            <td><div class="kpi-n alt">{{ number_format($totales['activos']) }}</div><div class="kpi-l">Activos (públicos)</div></td>
            <td><div class="kpi-n">{{ number_format($totales['inactivos']) }}</div><div class="kpi-l">Dados de baja</div></td>
            <td><div class="kpi-n gold">{{ number_format($totales['destacados']) }}</div><div class="kpi-l">Destacados</div></td>
        </tr>
    </table>

    @if(count($porCategoria))
        <h2 class="sec">Distribución por categoría</h2>
        <table class="data">
            <thead><tr><th>Categoría</th><th class="num" style="width:18%">Lugares</th></tr></thead>
            <tbody>
                @foreach($porCategoria as $cat => $n)
                    <tr><td>{{ $cat }}</td><td class="num">{{ number_format($n) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2 class="sec">Detalle de lugares</h2>
    @if(count($filas))
        <table class="data">
            <thead>
                <tr>
                    <th style="width:28%">Nombre</th>
                    <th style="width:17%">Categoría</th>
                    <th style="width:25%">Dirección</th>
                    <th style="width:14%">Teléfono</th>
                    <th style="width:16%">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($filas as $f)
                    <tr>
                        <td>{{ $f['nombre'] }}</td>
                        <td>{{ $f['categoria'] }}</td>
                        <td>{{ $f['direccion'] }}</td>
                        <td>{{ $f['telefono'] }}</td>
                        <td>
                            <span class="badge {{ $f['activo'] ? 'ok' : 'off' }}">{{ $f['activo'] ? 'Activo' : 'Baja' }}</span>
                            @if($f['destacado'])<span class="badge gold">Destacado</span>@endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No hay lugares turísticos registrados en el sistema.</div>
    @endif

@endsection
