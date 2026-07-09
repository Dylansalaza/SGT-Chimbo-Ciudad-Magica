<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index()
    {
        return Gallery::orderByDesc('created_at')->paginate(10);
    }

    public function show(Gallery $gallery)
    {
        return $gallery;
    }

    public function store(Request $request)
    {
        // La autorización de administrador la aplica el middleware 'admin' en las rutas.
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'images' => 'required|array',
        ]);

        $gallery = Gallery::create($validated);
        return response()->json($gallery, 201);
    }

    public function update(Request $request, Gallery $gallery)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'images' => 'sometimes|required|array',
        ]);

        $gallery->update($validated);
        return response()->json($gallery);
    }

    public function destroy(Gallery $gallery)
    {
        $gallery->delete();
        return response()->json(['message' => 'Galería eliminada']);
    }
}