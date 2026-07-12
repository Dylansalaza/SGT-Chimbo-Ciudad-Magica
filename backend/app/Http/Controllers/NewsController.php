<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

/**
 * API pública/REST de Noticias (rutas en routes/api.php).
 *
 * index/show son públicos; store/update/destroy están detrás de
 * 'auth:sanctum' + 'admin'. El panel Blade usa Admin\NoticiaController; este
 * es el que consume el frontend React.
 */
class NewsController extends Controller
{
    /** GET /news — lista pública paginada (10 por página), más recientes primero. */
    public function index()
    {
        return News::orderByDesc('published_at')->paginate(10);
    }

    /** GET /news/{news} — devuelve una noticia (route-model binding implícito). */
    public function show(News $news)
    {
        return $news;
    }

    /** POST /news — crea una noticia. Solo admin (middleware en la ruta). */
    public function store(Request $request)
    {
        // La autorización de administrador la aplica el middleware 'admin' en las rutas.
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'published_at' => 'nullable|date',
        ]);

        $news = News::create($validated);
        return response()->json($news, 201);
    }

    /** PUT /news/{news} — actualiza una noticia. Solo admin. */
    public function update(Request $request, News $news)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string',
            'published_at' => 'nullable|date',
        ]);

        $news->update($validated);
        return response()->json($news);
    }

    /** DELETE /news/{news} — elimina una noticia. Solo admin. */
    public function destroy(News $news)
    {
        $news->delete();
        return response()->json(['message' => 'Noticia eliminada']);
    }
}