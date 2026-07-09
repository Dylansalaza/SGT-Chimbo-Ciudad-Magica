<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlaceCategory;
use Illuminate\Http\Request;

class CategoriaLugarController extends Controller
{
    public function index()
    {
        $categorias = PlaceCategory::orderBy('nombre')->get();
        return view('admin.categorias.index', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:place_categories,nombre',
            'icono'  => 'nullable|string|max:10',
        ], [
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
        ]);

        PlaceCategory::create([
            'nombre' => trim($request->nombre),
            'icono'  => $request->icono ?: '📍',
        ]);

        return redirect()->route('admin.categorias.index')
            ->with('success', 'Categoría "' . $request->nombre . '" creada correctamente.');
    }

    public function destroy($id)
    {
        $categoria = PlaceCategory::findOrFail($id);
        $nombre = $categoria->nombre;
        $categoria->delete();

        return redirect()->route('admin.categorias.index')
            ->with('success', 'Categoría "' . $nombre . '" eliminada.');
    }
}
