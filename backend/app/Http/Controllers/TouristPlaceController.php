<?php

namespace App\Http\Controllers;

use App\Models\TouristPlace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

/**
 * API pública (consumida por el frontend React: mapa, Home, IA, etc.).
 * A diferencia de LugarController (admin), aquí solo se exponen los lugares
 * con "activo = true", para que los que fueron dados de baja desaparezcan
 * del sitio público sin perder su registro en la base de datos.
 */
class TouristPlaceController extends Controller
{   //NOMBRE UNICO PARA LA LLAVE DE LA CACHE EN MEMORIA RAM
    private $cacheKey = 'catalogo_turismo_key';

    /** Lista los lugares activos (con caché de 10 min) para el mapa/home público. */
    public function index()
    {
// El servidor recordará la consulta ordenada por 10 minutos (600 segundos)
        return Cache::remember($this->cacheKey, 600, function () {
            // Los lugares "dados de baja" no se muestran al público (mapa, home, búsqueda).
            return TouristPlace::where('activo', true)->orderBy('created_at', 'desc')->get();
        });
    }

    /**
     * Devuelve un único lugar turístico.
     * La ruta GET /tourist-places/{id} apuntaba a este método que no existía.
     */
    public function show($id)
    {
        return response()->json(TouristPlace::findOrFail($id));
    }

    /**
     * Catálogo para el motor CLIP: cada lugar con TODAS sus imágenes de referencia
     * (portada + galería). Mientras más fotos reales tenga un lugar, mejor lo
     * reconoce el sistema desde distintos ángulos.
     */
    public function clipCatalog()
    {
        // El motor de búsqueda visual (CLIP) tampoco debe reconocer lugares dados de baja.
        $places = TouristPlace::where('activo', true)->get();

        $catalogo = $places->map(function ($p) {
            $imgs = [];
            if ($p->imagen_url) {
                $imgs[] = $p->imagen_url;
            }
            foreach ((array) ($p->galeria ?? []) as $g) {
                $url = is_array($g) ? ($g['url'] ?? null) : $g;
                if ($url) {
                    $imgs[] = $url;
                }
            }

            return [
                'id'     => $p->id,
                'images' => array_values(array_unique(array_filter($imgs))),
            ];
        });

        return response()->json($catalogo);
    }

    /** Crea un lugar turístico vía API (uso programático/externo). */
    public function store(Request $request)
    {
        // Validamos y usamos SOLO los datos validados (nunca $request->all(),
        // que permitiría mass-assignment de campos internos como `activo`).
        $datos = $request->validate([
            'nombre'      => 'required|string|max:255',
            'categoria'   => 'required|string|max:255',
            'descripcion' => 'required|string',
            'lat'         => 'required|numeric|between:-90,90',
            'lng'         => 'required|numeric|between:-180,180',
            'direccion'   => 'required|string|max:255',
            'telefono'    => 'nullable|string|max:50',
            'horario'     => 'nullable|string|max:255',
            'precio'      => 'nullable|string|max:100',
            'imagen_url'  => 'required|url',
            'destacado'   => 'nullable|boolean',
            'galeria'     => 'nullable|array',
            'galeria.*'   => 'string',
        ]);

        $place = TouristPlace::create($datos);
        // 🔄 Limpiamos la caché porque hay un nuevo elemento
        Cache::forget($this->cacheKey);
        return response()->json($place, 201);
    }

    /** Actualiza un lugar turístico vía API (uso programático/externo). */
    public function update(Request $request, $id)
    {
        $place = TouristPlace::findOrFail($id);

        // Igual que en store: validación explícita y whitelist. En un PUT los
        // campos pueden venir parciales, por eso usamos `sometimes`.
        $datos = $request->validate([
            'nombre'      => 'sometimes|required|string|max:255',
            'categoria'   => 'sometimes|required|string|max:255',
            'descripcion' => 'sometimes|required|string',
            'lat'         => 'sometimes|required|numeric|between:-90,90',
            'lng'         => 'sometimes|required|numeric|between:-180,180',
            'direccion'   => 'sometimes|nullable|string|max:255',
            'telefono'    => 'sometimes|nullable|string|max:50',
            'horario'     => 'sometimes|nullable|string|max:255',
            'precio'      => 'sometimes|nullable|string|max:100',
            'imagen_url'  => 'sometimes|required|url',
            'destacado'   => 'sometimes|boolean',
            'galeria'     => 'sometimes|array',
            'galeria.*'   => 'string',
        ]);

        $place->update($datos);
        // 🔄 Limpiamos la caché para que los cambios modificados se actualicen
        Cache::forget($this->cacheKey);

        return response()->json($place);
    }

    /** Elimina un lugar turístico vía API (uso programático/externo; el panel admin usa "dar de baja" en su lugar). */
    public function destroy($id)
    {
        $place = TouristPlace::findOrFail($id);
        $place->delete();
        // 🔄 Limpiamos la caché porque se eliminó un registro
        Cache::forget($this->cacheKey);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             

        return response()->json(['message' => 'Eliminado correctamente']);
    }
}