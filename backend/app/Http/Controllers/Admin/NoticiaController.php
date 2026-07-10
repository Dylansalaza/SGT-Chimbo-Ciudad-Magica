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
        $noticias = News::orderBy('created_at', 'desc')->get();
        return view('admin.noticias.index', compact('noticias'));
    }

    /** Muestra el formulario para redactar una noticia nueva. */
    public function create()
    {
        return view('admin.noticias.create');
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
        return view('admin.noticias.edit', compact('noticia'));
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

        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $file = $request->file('file');
        // Convierte la foto a WebP (más liviano) cuando es posible.
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