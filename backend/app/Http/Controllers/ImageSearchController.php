<?php

namespace App\Http\Controllers;

use App\Contracts\ImageSearchInterface;
use App\Models\ImageSearch;
use App\Models\TouristPlace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageSearchController extends Controller
{
    /**
     * El motor de búsqueda se inyecta como abstracción (DIP). El controller no
     * sabe que detrás hay un servicio Flask; solo conoce el contrato.
     */
    public function __construct(
        private readonly ImageSearchInterface $engine,
    ) {
    }

    // ============================================================
    // RUTAS PÚBLICAS (IA - Flujo Asíncrono)
    // ============================================================

    /**
     * POST /image-search
     * Recibe la imagen desde React y crea el ticket en estado "pending".
     * El worker de Python lo recoge de la tabla `image_searches`.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:8192',
        ]);

        try {
            $path    = $request->file('image')->store('searches', 'public');
            $fullUrl = asset('storage/' . $path);

            $search = ImageSearch::create([
                'image_path' => $fullUrl,
                'status'     => 'pending',
            ]);

            return response()->json([
                'success'   => true,
                // Devolvemos ambas claves: el frontend leía 'search_id' mientras
                // que el backend solo enviaba 'id' (la búsqueda nunca arrancaba).
                'id'        => $search->id,
                'search_id' => $search->id,
                'status'    => 'pending',
                'message'   => 'Imagen encolada. Procesando con Inteligencia Artificial...',
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Error interno al procesar la imagen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /image-search/status/{id}
     * React hace polling aquí hasta que el worker termina.
     */
    public function checkStatus($id): JsonResponse
    {
        $search = ImageSearch::find($id);

        if (! $search) {
            return response()->json(['success' => false, 'error' => 'Ticket no encontrado.'], 404);
        }

        $payload = [
            'success'   => true,
            'search_id' => $search->id,
            'status'    => $search->status,
            'result'    => null,
            'score'     => null,
            'similares' => [],
            'error'     => null,
        ];

        if ($search->status === 'completed') {
            // Lugar ganador: el frontend necesita el objeto completo (lat/lng/nombre),
            // no solo el id, para centrar el mapa y mostrar la ficha.
            $payload['result'] = TouristPlace::find($search->tourist_place_id);
            $payload['score']  = $search->top_score;

            // Si no hubo coincidencia confiable (por debajo del umbral), el worker
            // dejó tourist_place_id en NULL y un mensaje en error_message.
            if (! $payload['result']) {
                $payload['error'] = $search->error_message
                    ?: 'No se reconoció ningún lugar con suficiente confianza.';
            }

            // "Similares": el resto de candidatos (excluyendo el ganador), resueltos
            // a objetos TouristPlace para que la UI los pueda renderizar.
            $candidates = json_decode($search->candidates ?? '[]', true) ?: [];
            $similarIds = collect($candidates)
                ->pluck('id')
                ->reject(fn ($cid) => (int) $cid === (int) $search->tourist_place_id)
                ->take(4)
                ->all();

            if (! empty($similarIds)) {
                // Preservamos el orden por score devuelto por CLIP.
                $payload['similares'] = TouristPlace::whereIn('id', $similarIds)
                    ->get()
                    ->sortBy(fn ($place) => array_search($place->id, $similarIds))
                    ->values();
            }
        }

        if ($search->status === 'failed') {
            $payload['error'] = $search->error_message;
        }

        return response()->json($payload);
    }

    // ============================================================
    // RUTAS PROTEGIDAS (Solo Administradores)
    // ============================================================

    /**
     * POST /image-search/refresh
     * Ordena al motor reindexar el catálogo CLIP.
     */
    public function refresh(): JsonResponse
    {
        try {
            return response()->json($this->engine->refreshIndex());
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'No se pudo conectar con el motor de IA. ¿Está encendido? Detalle: ' . $e->getMessage(),
            ], 503);
        }
    }

    /**
     * GET /image-search/health
     */
    public function health(): JsonResponse
    {
        try {
            return response()->json($this->engine->health());
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'offline',
                'error'  => 'El motor de IA está apagado o inaccesible.',
            ], 503);
        }
    }
}
