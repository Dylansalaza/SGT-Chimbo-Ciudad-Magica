<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GaleriaController extends Controller
{
    public function index()
    {
        $galerias = Gallery::orderBy('created_at', 'desc')->get();
        return view('admin.galerias.index', compact('galerias'));
    }

    public function create()
    {
        return view('admin.galerias.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'images'   => 'required|array',
            'images.*' => 'string',
        ]);

        Gallery::create([
            'title'  => $request->title,
            'images' => $request->images, // Conserva el orden establecido en el frontend
        ]);

        return redirect()->route('admin.galerias.index')
                         ->with('success', 'Galería creada correctamente');
    }

    /**
     * 🟢 MUESTRA EL FORMULARIO DE EDICIÓN
     */
    public function edit($id)
    {
        $galeria = Gallery::findOrFail($id);
        return view('admin.galerias.edit', compact('galeria'));
    }

    /**
     * 🟢 PROCESA LA ACTUALIZACIÓN DE LA GALERÍA
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'images'   => 'required|array',
            'images.*' => 'string',
        ]);

        $galeria = Gallery::findOrFail($id);
        $galeria->update([
            'title'  => $request->title,
            'images' => $request->images,
        ]);

        return redirect()->route('admin.galerias.index')
                         ->with('success', 'Galería actualizada correctamente');
    }

    public function destroy($id)
    {
        $galeria = Gallery::findOrFail($id);

        if ($galeria->images) {
            foreach ($galeria->images as $imagePath) {
                $path = str_replace('/storage/', '', $imagePath);
                Storage::disk('public')->delete($path);
            }
        }

        $galeria->delete();

        return redirect()->route('admin.galerias.index')
                         ->with('success', 'Galería eliminada correctamente');
    }

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
        $path = \App\Support\ImageOptimizer::storeWebp($file, 'galerias');

        return response()->json(['url' => '/storage/' . $path]);
    }
}