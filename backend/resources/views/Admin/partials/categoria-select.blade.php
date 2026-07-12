{{-- Selector de categoría con opción "Otra…".
     Parámetros:
       $categorias : array de categorías disponibles (del controlador).
       $current    : valor actual (opcional; por defecto old('categoria')).

     Al elegir "Otra…" se muestra un campo de texto para escribir una categoría
     nueva. En el submit, ese texto se envía como `categoria` (el marcador
     "__otra__" nunca llega al servidor). Si la validación falla y vuelve un
     valor que no está en la lista, se reabre "Otra…" con el texto preservado. --}}
@php
    $current = $current ?? old('categoria');
    $esNueva = $current !== null && $current !== '' && ! in_array($current, $categorias, true);
@endphp
<div class="relative" data-categoria-wrap>
    <select name="categoria" id="categoria" data-categoria-select
            class="w-full appearance-none pr-10 px-4 py-2.5 rounded-xl border border-slate-200 bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 outline-none text-sm transition">
        <option value="">— Selecciona una categoría —</option>
        @foreach($categorias as $cat)
            <option value="{{ $cat }}" {{ ! $esNueva && $current === $cat ? 'selected' : '' }}>{{ $cat }}</option>
        @endforeach
        <option value="__otra__" {{ $esNueva ? 'selected' : '' }}>Otra…</option>
    </select>
    <i class="fas fa-chevron-down pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
</div>
<input type="text" data-categoria-otra autocomplete="off"
       value="{{ $esNueva ? $current : '' }}"
       placeholder="Escribe la nueva categoría"
       class="mt-2 w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-green-600 focus:ring-2 focus:ring-green-600/20 outline-none text-sm transition {{ $esNueva ? '' : 'hidden' }}">

@push('scripts')
<script>
    (function () {
        const wrap   = document.querySelector('[data-categoria-wrap]');
        if (!wrap) return;
        const select = wrap.querySelector('[data-categoria-select]');
        const otra   = wrap.parentElement.querySelector('[data-categoria-otra]');
        const form   = select.closest('form');

        function sync() {
            const esOtra = select.value === '__otra__';
            otra.classList.toggle('hidden', !esOtra);
            if (esOtra) otra.focus();
        }
        select.addEventListener('change', sync);

        // Al enviar: si está "Otra…", mandamos el texto escrito como `categoria`
        // (quitamos el name del select para que no gane el marcador "__otra__").
        form?.addEventListener('submit', function () {
            if (select.value === '__otra__') {
                select.removeAttribute('name');
                otra.setAttribute('name', 'categoria');
            }
        });
    })();
</script>
@endpush
