<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSetting;
use App\Models\News;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $imagenesSubidas = $this->listarImagenesHome();
        return view('admin.home.edit', compact('settings', 'todasNoticias', 'todosEventos', 'imagenesSubidas'));
    }

    /**
     * Todas las imágenes que se han subido alguna vez al carrusel (disco
     * `public/home`), más recientes primero. Sirve para reutilizarlas en una
     * nueva diapositiva o borrarlas definitivamente del servidor.
     */
    private function listarImagenesHome(): array
    {
        $archivos = collect(Storage::disk('public')->files('home'))
            ->sortByDesc(fn ($path) => Storage::disk('public')->lastModified($path))
            ->values();

        return $archivos->map(fn ($path) => [
            'nombre' => basename($path),
            'url'    => '/storage/' . $path,
        ])->all();
    }

    /** Borra definitivamente una imagen subida del disco (si ya no está en uso en el carrusel). */
    public function destroyImage(Request $request, string $nombre)
    {
        $nombre = basename($nombre); // evita path traversal
        $path = 'home/' . $nombre;

        $enUso = collect(HomeSetting::singleton()->carousel ?? [])
            ->contains(fn ($slide) => ($slide['url'] ?? null) === '/storage/' . $path);

        if ($enUso) {
            return response()->json([
                'error' => 'Esta imagen está en uso en el carrusel actual. Quítala del carrusel y guarda antes de borrarla.',
            ], 422);
        }

        if (! Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'La imagen ya no existe.'], 404);
        }

        Storage::disk('public')->delete($path);

        return response()->json(['ok' => true]);
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

        return redirect()->route('admin.home.edit')->with('success', 'Home actualizado correctamente.');
    }

    /** Sube una imagen del carrusel y devuelve su URL. */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $file = $request->file('file');
        $nombre = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        $path = $file->storeAs('home', $nombre, 'public');

        return response()->json(['url' => '/storage/' . $path]);
    }
}
