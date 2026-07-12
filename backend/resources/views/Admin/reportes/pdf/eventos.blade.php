@extends('admin.reportes.pdf.layout')

@section('titulo', 'Reporte de Eventos')
@section('subtitulo', 'Agenda cultural y turística registrada en el sistema')
@section('codigo', 'SGT-REP-EVE')

@section('contenido')

    <table class="kpis">
        <tr>
            <td><div class="kpi-n">{{ number_format($totales['total']) }}</div><div class="kpi-l">Eventos registrados</div></td>
            <td><div class="kpi-n alt">{{ number_format($totales['vigentes']) }}</div><div class="kpi-l">Vigentes / próximos</div></td>
            <td><div class="kpi-n gold">{{ number_format($totales['pasados']) }}</div><div class="kpi-l">Finalizados</div></td>
            <td><div class="kpi-n">{{ count($porCategoria) }}</div><div class="kpi-l">Categorías</div></td>
        </tr>
    </table>

    @if(count($porCategoria))
        <h2 class="sec">Distribución por categoría</h2>
        <table class="data">
            <thead><tr><th>Categoría</th><th class="num" style="width:18%">Eventos</th></tr></thead>
            <tbody>
                @foreach($porCategoria as $cat => $n)
                    <tr><td>{{ $cat }}</td><td class="num">{{ number_format($n) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2 class="sec">Detalle de eventos</h2>
    @if(count($filas))
        <table class="data">
            <thead>
                <tr>
                    <th style="width:34%">Evento</th>
                    <th style="width:16%">Categoría</th>
                    <th style="width:19%">Inicio</th>
                    <th style="width:19%">Finalización</th>
                    <th style="width:12%">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($filas as $f)
                    <tr>
                        <td>{{ $f['title'] }}</td>
                        <td>{{ $f['categoria'] }}</td>
                        <td>{{ $f['inicio'] }}</td>
                        <td>{{ $f['fin'] }}</td>
                        <td><span class="badge {{ $f['pasado'] ? 'off' : 'ok' }}">{{ $f['estado'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No hay eventos registrados en el sistema.</div>
    @endif

@endsection
