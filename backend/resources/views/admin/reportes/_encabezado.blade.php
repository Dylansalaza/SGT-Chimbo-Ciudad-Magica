{{-- Encabezado reutilizable de la vista en pantalla de un reporte.
     Vars: $titulo, $subtitulo, $pdfRoute, (opcional) $csvRoute, $generado --}}
<div class="sticky top-0 z-50 header-corporate text-white w-full px-10 shadow-lg border-b border-white/5">
    <div class="w-full flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4 py-1">
        <div class="space-y-1">
            <a href="{{ route('admin.reportes.index') }}" class="text-xs text-slate-300 hover:text-white inline-flex items-center gap-1.5 mb-1"><i class="fas fa-arrow-left"></i> Reportes</a>
            <h1 class="font-serif text-2xl font-extrabold tracking-tight md:text-3xl">{{ $titulo }}</h1>
            <p class="text-sm text-slate-300 font-medium">{{ $subtitulo }}</p>
        </div>
        <div class="flex items-center gap-2 self-start lg:self-center">
            @isset($csvRoute)
                <a href="{{ route($csvRoute) }}" class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white px-4 py-2.5 rounded-xl text-xs font-black tracking-wider uppercase transition">
                    <i class="fas fa-file-csv"></i> Excel (CSV)
                </a>
            @endisset
            <a href="{{ route($pdfRoute) }}" class="inline-flex items-center gap-2 bg-white text-[#00913f] px-5 py-2.5 rounded-xl text-xs font-black tracking-wider shadow-md uppercase hover:bg-slate-100 transition">
                <i class="fas fa-file-pdf"></i> Descargar PDF
            </a>
        </div>
    </div>
</div>
@isset($generado)
<p class="px-10 pt-4 text-xs text-slate-400"><i class="far fa-clock"></i> Generado el {{ $generado }}</p>
@endisset
