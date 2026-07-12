@extends('admin.reportes.pdf.layout')

@section('titulo', 'Reporte de Noticias')
@section('subtitulo', 'Publicaciones informativas del portal turístico')
@section('codigo', 'SGT-REP-NOT')

@section('contenido')

    <table class="kpis">
        <tr>
            <td><div class="kpi-n">{{ number_format($totales['total']) }}</div><div class="kpi-l">Noticias publicadas</div></td>
            <td><div class="kpi-n alt">{{ number_format($totales['mes']) }}</div><div class="kpi-l">Publicadas este mes</div></td>
            <td><div class="kpi-n">{{ count($porCategoria) }}</div><div class="kpi-l">Categorías</div></td>
        </tr>
    </table>

    @if(count($porCategoria))
        <h2 class="sec">Distribución por categoría</h2>
        <table class="data">
            <thead><tr><th>Categoría</th><th class="num" style="width:18%">Noticias</th></tr></thead>
            <tbody>
                @foreach($porCategoria as $cat => $n)
                    <tr><td>{{ $cat }}</td><td class="num">{{ number_format($n) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2 class="sec">Detalle de publicaciones</h2>
    @if(count($filas))
        <table class="data">
            <thead>
                <tr>
                    <th style="width:52%">Título</th>
                    <th style="width:22%">Categoría</th>
                    <th style="width:26%">Fecha de publicación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($filas as $f)
                    <tr>
                        <td>{{ $f['title'] }}</td>
                        <td>{{ $f['categoria'] }}</td>
                        <td>{{ $f['fecha'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No hay noticias publicadas en el sistema.</div>
    @endif

@endsection
