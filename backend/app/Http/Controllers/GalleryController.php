<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\Http\Request;

/**
 * API pública/REST de Galerías de imágenes (rutas en routes/api.php).
 *
 * index/show son públicos; store/update/destroy están detrás de
 * 'auth:sanctum' + 'admin'. El panel Blade usa Admin\GaleriaController; este
 * es el que consume el frontend React.
 */
class GalleryController extends Controller
{
    /** GET /galleries — lista pública paginada (10 por página), más recientes primero. */
    public function index()
    {
        return Gallery::orderByDesc('created_at')->paginate(10);
    }

    /** GET /galleries/{gallery} — devuelve una galería (route-model binding implícito). */
    public function show(Gallery $gallery)
    {
        return $gallery;
    }

    /** POST /galleries — crea una galería. Solo admin (middleware en la ruta). */
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

    /** PUT /galleries/{gallery} — actualiza una galería. Solo admin. */
    public function update(Request $request, Gallery $gallery)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'images' => 'sometimes|required|array',
        ]);

        $gallery->update($validated);
        return response()->json($gallery);
    }

    /** DELETE /galleries/{gallery} — elimina una galería. Solo admin. */
    public function destroy(Gallery $gallery)
    {
        $gallery->delete();
        return response()->json(['message' => 'Galería eliminada']);
    }
}