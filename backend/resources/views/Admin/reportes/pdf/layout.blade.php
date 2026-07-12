<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>@yield('titulo', 'Reporte') — SGT Chimbo</title>
<style>
    /* ── Página y tipografía (dompdf: DejaVu Sans soporta acentos) ── */
    @page { margin: 3.1cm 1.6cm 2.4cm 1.6cm; }
    * { box-sizing: border-box; }
    body {
        font-family: "DejaVu Sans", sans-serif;
        color: #1e293b;
        font-size: 10.5px;
        line-height: 1.45;
        margin: 0;
    }

    /* ── Cabecera fija (membrete institucional) en TODAS las páginas ── */
    .lh { position: fixed; top: -2.35cm; left: 0; right: 0; height: 2.2cm; }
    .lh table { width: 100%; border-collapse: collapse; }
    .lh td { vertical-align: middle; border: 0; }
    .brand-mark {
        width: 46px; height: 46px; background: #00913f; border-radius: 11px;
        color: #fff; font-size: 26px; font-weight: bold; text-align: center;
        line-height: 40px;
    }
    .brand-mark .bar { display: block; height: 3px; width: 24px; margin: 1px auto 0; background: #fff; border-radius: 2px; }
    .brand-mark .bar.gold { width: 14px; background: #F2C230; margin-top: 2px; }
    .org-name { font-size: 14px; font-weight: bold; color: #04521f; letter-spacing: .3px; }
    .org-sub  { font-size: 9px; color: #475569; letter-spacing: .5px; text-transform: uppercase; }
    .org-loc  { font-size: 8.5px; color: #94a3b8; }
    .lh-code  { text-align: right; font-size: 8.5px; color: #64748b; }
    .lh-code b { color: #334155; }
    .lh-rule  { height: 2.5px; background: #00913f; margin-top: 6px; }
    .lh-rule .g { float: right; width: 38%; height: 2.5px; background: #F2C230; }

    /* ── Pie fijo en TODAS las páginas ── */
    .ft { position: fixed; bottom: -1.7cm; left: 0; right: 0; height: 1.4cm; font-size: 8px; color: #94a3b8; }
    .ft-rule { height: 1px; background: #e2e8f0; margin-bottom: 4px; }
    .ft table { width: 100%; border-collapse: collapse; }
    .ft td { border: 0; }
    .ft .r { text-align: right; }
    .pageof:after { content: "Página " counter(page) " de " counter(pages); }

    /* ── Título del documento ── */
    .doc-title { margin: 0 0 2px; }
    .doc-kicker { font-size: 8.5px; letter-spacing: 3px; text-transform: uppercase; color: #00913f; font-weight: bold; }
    .doc-h1 { font-size: 20px; font-weight: bold; color: #0f172a; margin: 2px 0 3px; }
    .doc-sub { font-size: 10px; color: #64748b; }
    .doc-meta { margin-top: 8px; margin-bottom: 16px; }
    .doc-meta table { width: 100%; border-collapse: collapse; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
    .doc-meta td { padding: 6px 10px; font-size: 9px; border: 0; border-right: 1px solid #e2e8f0; }
    .doc-meta td:last-child { border-right: 0; }
    .doc-meta .k { color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; font-size: 7.5px; display: block; }
    .doc-meta .v { color: #334155; font-weight: bold; }

    /* ── Tarjetas de indicadores (KPIs) ── */
    .kpis { width: 100%; border-collapse: separate; border-spacing: 7px 0; margin: 0 0 18px; }
    .kpis td { width: 25%; background: #f8fafc; border: 1px solid #e6ebf1; border-radius: 8px; padding: 11px 8px; text-align: center; }
    .kpi-n { font-size: 21px; font-weight: bold; color: #00913f; }
    .kpi-n.alt { color: #04521f; }
    .kpi-n.gold { color: #b8860b; }
    .kpi-l { font-size: 7.5px; text-transform: uppercase; letter-spacing: .6px; color: #64748b; margin-top: 2px; }

    /* ── Secciones y tablas de datos ── */
    h2.sec { font-size: 12px; color: #0f172a; margin: 18px 0 7px; padding-bottom: 5px; border-bottom: 1.5px solid #00913f; }
    h2.sec span { color: #94a3b8; font-size: 9px; font-weight: normal; }
    table.data { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.data th {
        background: #00913f; color: #fff; font-size: 8.5px; text-transform: uppercase;
        letter-spacing: .4px; padding: 6px 8px; text-align: left; border: 1px solid #00913f;
    }
    table.data td { border: 1px solid #e2e8f0; padding: 5px 8px; font-size: 9.5px; }
    table.data tbody tr:nth-child(even) td, table.data tr:nth-child(even) td { background: #f8fafc; }
    table.data .num { text-align: right; }
    table.data tfoot td { background: #eef6f0; font-weight: bold; border-color: #cfe6d7; }
    .badge { font-size: 8px; padding: 1px 7px; border-radius: 8px; font-weight: bold; }
    .badge.ok  { background: #dcfce7; color: #15803d; }
    .badge.off { background: #f1f5f9; color: #64748b; }
    .badge.gold{ background: #fef3c7; color: #b45309; }
    .empty { text-align: center; color: #94a3b8; padding: 18px; font-style: italic; border: 1px dashed #cbd5e1; border-radius: 6px; }

    /* ── Área de firma ── */
    .firmas { margin-top: 34px; }
    .firmas table { width: 100%; border-collapse: collapse; }
    .firmas td { width: 50%; text-align: center; padding: 0 22px; font-size: 9px; color: #475569; border: 0; }
    .firma-line { border-top: 1px solid #64748b; margin: 0 10px 4px; padding-top: 4px; }
    .firma-role { font-weight: bold; color: #334155; }
</style>
</head>
<body>

    {{-- Cabecera fija: membrete municipal --}}
    <div class="lh">
        <table>
            <tr>
                <td style="width:52px">
                    <div class="brand-mark">S<span class="bar"></span><span class="bar gold"></span></div>
                </td>
                <td>
                    <div class="org-name">Municipio de San José de Chimbo</div>
                    <div class="org-sub">Sistema de Gestión Turística</div>
                    <div class="org-loc">Provincia de Bolívar — Ecuador</div>
                </td>
                <td class="lh-code">
                    <div><b>DOC.</b> @yield('codigo', 'SGT-REP')</div>
                    <div><b>Emisión:</b> {{ $generado ?? '' }}</div>
                </td>
            </tr>
        </table>
        <div class="lh-rule"><span class="g"></span></div>
    </div>

    {{-- Pie fijo: nota + numeración --}}
    <div class="ft">
        <div class="ft-rule"></div>
        <table>
            <tr>
                <td>Documento generado automáticamente por el Sistema de Gestión Turística — Municipio de San José de Chimbo.</td>
                <td class="r"><span class="pageof"></span></td>
            </tr>
        </table>
    </div>

    {{-- Título del documento --}}
    <div class="doc-title">
        <div class="doc-kicker">Reporte institucional</div>
        <div class="doc-h1">@yield('titulo')</div>
        <div class="doc-sub">@yield('subtitulo')</div>
    </div>

    <div class="doc-meta">
        <table>
            <tr>
                <td><span class="k">Generado el</span><span class="v">{{ $generado ?? '' }}</span></td>
                @hasSection('periodo')
                    <td><span class="k">Periodo</span><span class="v">@yield('periodo')</span></td>
                @endif
                <td><span class="k">Emitido por</span><span class="v">Panel Administrativo SGT</span></td>
            </tr>
        </table>
    </div>

    {{-- Contenido del reporte --}}
    @yield('contenido')

    {{-- Área de validación / firma (al final del documento) --}}
    <div class="firmas">
        <table>
            <tr>
                <td>
                    <div class="firma-line"></div>
                    <div class="firma-role">Elaborado por</div>
                    <div>Sistema de Gestión Turística</div>
                </td>
                <td></td>
            </tr>
        </table>
    </div>

</body>
</html>
