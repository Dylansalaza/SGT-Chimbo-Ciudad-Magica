<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        return News::orderByDesc('published_at')->paginate(10);
    }

    public function show(News $news)
    {
        return $news;
    }

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

    public function destroy(News $news)
    {
        $news->delete();
        return response()->json(['message' => 'Noticia eliminada']);
    }
}