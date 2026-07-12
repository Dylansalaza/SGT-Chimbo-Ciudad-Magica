<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Controlador ADMIN de Noticias (CRUD del panel Blade). Las noticias
 * alimentan la sección "Noticias recientes" del Home y la página pública
 * /noticias (estilo periódico), donde se marcan como "actual" solo el día
 * en que se publicaron.
 */
class NoticiaController extends Controller
{
    /** Lista todas las noticias para la tabla del admin. */
    public function index()
    {
        // Ordena por la FECHA de la noticia (más reciente arriba). Si dos comparten
        // fecha —o alguna no tiene fecha— se desempata por la de creación.
        $noticias = News::orderByDesc('published_at')->orderByDesc('created_at')->get();
        return view('admin.noticias.index', compact('noticias'));
    }

    /** Muestra el formulario para redactar una noticia nueva. */
    public function create()
    {
        return view('admin.noticias.create', ['categorias' => $this->categoriasDisponibles()]);
    }

    /**
     * Lista de categorías para el <select> de noticias: combina un conjunto
     * base con las que ya se han usado en noticias existentes, sin duplicados
     * y ordenadas alfabéticamente.
     */
    private function categoriasDisponibles(): array
    {
        $base = ['Política', 'Cultura', 'Deportes', 'Comunidad', 'Turismo', 'Economía'];

        $usadas = News::query()
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->pluck('categoria')
            ->all();

        $todas = array_unique(array_merge($base, $usadas));
        sort($todas, SORT_NATURAL | SORT_FLAG_CASE);

        return $todas;
    }

    /** Valida y guarda una noticia nueva, combinando fecha+hora de publicación. */
    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'categoria'      => 'nullable|string|max:255',
            'body'           => 'required|string',
            'published_date' => 'nullable|date',
            'published_time' => 'nullable|string',
            'image_url'      => 'nullable|string',
            'images'         => 'nullable|array',
            'images.*'       => 'string',
        ]);

        // Concatenamos la fecha y la hora si la fecha fue seleccionada
        $published_at = null;
        if ($request->published_date) {
            $time = $request->published_time ?? '00:00';
            $published_at = $request->published_date . ' ' . $time . ':00';
        }

        $noticia = News::create([
            'title'        => $request->title,
            'categoria'    => $request->categoria,
            'body'         => $request->body,
            'published_at' => $published_at,
            'image_url'    => $request->image_url,
            'images'       => array_values(array_filter($request->input('images', []))),
        ]);

        $this->sincronizarGaleria($noticia);

        return redirect()->route('admin.noticias.index')
                         ->with('success', 'Noticia creada correctamente');
    }

    /**
     * 🟢 MUESTRA EL FORMULARIO DE EDICIÓN CON LOS DATOS DE LA NOTICIA
     */
    public function edit($id)
    {
        $noticia = News::findOrFail($id);
        return view('admin.noticias.edit', [
            'noticia'    => $noticia,
            'categorias' => $this->categoriasDisponibles(),
        ]);
    }

    /**
     * 👁️ VISTA DE SOLO LECTURA — para revisar la noticia sin entrar a editarla.
     */
    public function show($id)
    {
        $noticia = News::findOrFail($id);
        return view('admin.noticias.show', compact('noticia'));
    }

    /**
     * 🟢 PROCESA LA ACTUALIZACIÓN EN LA BASE DE DATOS
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'categoria'    => 'nullable|string|max:255',
            'body'         => 'required|string',
            'published_at' => 'nullable|date',
            'image_url'    => 'nullable|string',
            'images'       => 'nullable|array',
            'images.*'     => 'string',
        ]);

        $noticia = News::findOrFail($id);

        $noticia->update([
            'title'        => $request->title,
            'categoria'    => $request->categoria,
            'body'         => $request->body,
            'published_at' => $request->published_at,
            'image_url'    => $request->image_url,
            'images'       => array_values(array_filter($request->input('images', []))),
        ]);

        $this->sincronizarGaleria($noticia);

        return redirect()->route('admin.noticias.index')
                         ->with('success', 'Noticia actualizada correctamente');
    }

    /**
     * Mantiene sincronizada la galería pública de la noticia con sus
     * fotos (portada + galería adicional), igual que hace EventoController,
     * para que cada foto subida aparezca automáticamente en Galerías.
     */
    private function sincronizarGaleria(News $noticia): void
    {
        $medios = array_values(array_unique(array_filter(array_merge(
            $noticia->image_url ? [$noticia->image_url] : [],
            $noticia->images ?? []
        ))));

        if (empty($medios)) {
            Gallery::where('news_id', $noticia->id)->delete();
            return;
        }

        Gallery::updateOrCreate(
            ['news_id' => $noticia->id],
            [
                'title'    => $noticia->title,
                'category' => $noticia->categoria ?: 'Noticias',
                'images'   => $medios,
            ]
        );
    }

    /** Sube la portada o un archivo de galería de una noticia. */
    public function upload(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No se recibió ningún archivo'], 400);
        }

        // Acepta imágenes y también video (MP4/WebM/MOV/OGG). El tope de 40 MB
        // va en línea con los límites de PHP (upload_max_filesize/post_max_size).
        $request->validate([
            // Nota: algunos MP4 los detecta PHP (finfo) como "application/mp4"
            // en vez de "video/mp4"; incluimos ambos para no rechazarlos.
            'file' => 'required|file|mimetypes:image/jpeg,image/png,image/gif,image/webp,video/mp4,application/mp4,video/webm,video/quicktime,video/x-msvideo,video/ogg,application/ogg|max:40960',
        ], [
            'file.mimetypes' => 'Formato no permitido. Sube una imagen (JPG, PNG, GIF, WebP) o un video (MP4, WebM, MOV).',
            'file.max'       => 'El archivo supera el máximo de 40 MB.',
        ]);

        $file = $request->file('file');
        // Convierte la foto a WebP (más liviano) cuando es posible; los videos y
        // otros no-imagen se guardan tal cual (ImageOptimizer hace el fallback).
        $path = \App\Support\ImageOptimizer::storeWebp($file, 'noticias');

        return response()->json(['url' => '/storage/' . $path]);
    }

    /** Elimina definitivamente una noticia y su imagen de portada del storage. */
    public function destroy($id)
    {
        $noticia = News::findOrFail($id);

        if ($noticia->image_url) {
            $path = str_replace('/storage/', '', $noticia->image_url);
            Storage::disk('public')->delete($path);
        }

        Gallery::where('news_id', $noticia->id)->delete();
        $noticia->delete();

        return redirect()->route('admin.noticias.index')
                         ->with('success', 'Noticia eliminada correctamente');
    }
}