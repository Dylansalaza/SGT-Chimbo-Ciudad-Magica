<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSetting;
use App\Models\News;
use App\Models\Event;
use Illuminate\Http\Request;

/**
 * Editor del contenido del Home de React desde el panel administrativo.
 */
class HomeController extends Controller
{
    public function edit()
    {
        $settings = HomeSetting::singleton();
        $todasNoticias = News::orderByDesc('published_at')->get();
        $todosEventos  = Event::orderByDesc('starts_at')->get();
        return view('admin.home.edit', compact('settings', 'todasNoticias', 'todosEventos'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'welcome_title'      => 'required|string|max:255',
            'welcome_text'       => 'nullable|string',
            'carousel'           => 'nullable|array',
            'carousel.*.url'     => 'nullable|string',
            'carousel.*.title'   => 'nullable|string|max:255',
            'carousel.*.subtitle'=> 'nullable|string|max:255',
        ]);

        // Conservamos solo las diapositivas que tengan al menos una imagen.
        $carousel = collect($request->input('carousel', []))
            ->filter(fn ($slide) => ! empty($slide['url']))
            ->map(fn ($slide) => [
                'url'      => $slide['url'],
                'title'    => $slide['title'] ?? '',
                'subtitle' => $slide['subtitle'] ?? '',
            ])
            ->values()
            ->all();

        // Secciones visibles (checkboxes)
        $secciones = [
            'destacados' => $request->boolean('sec_destacados'),
            'noticias'   => $request->boolean('sec_noticias'),
            'eventos'    => $request->boolean('sec_eventos'),
        ];

        // IDs de noticias/eventos elegidos (checkboxes); se normalizan a enteros.
        $noticiasIds = array_map('intval', (array) $request->input('noticias_ids', []));
        $eventosIds  = array_map('intval', (array) $request->input('eventos_ids', []));

        $settings = HomeSetting::singleton();
        $settings->update([
            'welcome_title' => $request->welcome_title,
            'welcome_text'  => $request->welcome_text,
            'carousel'      => $carousel,
            'secciones'     => $secciones,
            'noticias_ids'  => $noticiasIds,
            'eventos_ids'   => $eventosIds,
        ]);

        // El contenido público cambió: invalidamos la caché de /home para que la
        // próxima visita reciba los datos nuevos de inmediato (sin esperar el TTL).
        \App\Http\Controllers\HomeController::olvidarCache();

        return redirect()->route('admin.home.edit')->with('success', 'Home actualizado correctamente.');
    }

    /** Sube una imagen del carrusel y devuelve su URL. */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $file = $request->file('file');
        // Convierte la foto a WebP (más liviano) cuando es posible.
        $path = \App\Support\ImageOptimizer::storeWebp($file, 'home');

        return response()->json(['url' => '/storage/' . $path]);
    }
}
