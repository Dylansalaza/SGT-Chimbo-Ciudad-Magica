<?php

namespace App\Http\Controllers;

use App\Models\HomeSetting;
use App\Models\TouristPlace;
use App\Models\News;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * API pública del contenido del Home de React.
 * Devuelve el carrusel, textos, secciones activas y los lugares/noticias/eventos
 * que el administrador eligió mostrar (todo editable desde el panel).
 */
class HomeController extends Controller
{
    /** Clave de caché de la respuesta pública de /home. */
    public const CACHE_KEY = 'home_payload_v1';

    public function show(): JsonResponse
    {
        // La respuesta se cachea unos minutos: el contenido cambia poco (solo
        // cuando el admin edita el Home o publica noticias/eventos), así evitamos
        // repetir las mismas consultas en cada visita. Se invalida al instante
        // cuando el admin guarda (ver Admin\HomeController::update → olvidarCache()).
        $payload = Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            return $this->construirPayload();
        });

        return response()->json($payload);
    }

    /** Arma el contenido del Home (consultas a BD). Separado para poder cachearlo. */
    private function construirPayload(): array
    {
        $settings = HomeSetting::singleton();

        // Secciones (por defecto todas visibles)
        $secciones = array_merge(
            ['destacados' => true, 'noticias' => true, 'eventos' => true],
            $settings->secciones ?? []
        );

        // Lugares destacados; si no hay ninguno marcado, mostramos los 6 más recientes.
        $destacados = TouristPlace::where('destacado', true)->orderByDesc('created_at')->get();
        if ($destacados->isEmpty()) {
            $destacados = TouristPlace::orderByDesc('created_at')->take(6)->get();
        }

        // Noticias: las elegidas por el admin, o las 3 más recientes si no eligió.
        $noticias = $this->seleccionar(News::class, $settings->noticias_ids, 'published_at');

        // Eventos: los elegidos por el admin, o los 3 más recientes si no eligió.
        $eventos = $this->seleccionar(Event::class, $settings->eventos_ids, 'starts_at');

        return [
            'welcome_title' => $settings->welcome_title,
            'welcome_text'  => $settings->welcome_text,
            'carousel'      => $settings->carousel ?? [],
            'secciones'     => $secciones,
            'destacados'    => $destacados,
            'noticias'      => $noticias,
            'eventos'       => $eventos,
        ];
    }

    /** Borra la caché de /home para que la próxima visita reciba datos frescos. */
    public static function olvidarCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Devuelve los registros con los IDs elegidos (en ese orden), o los 3 más
     * recientes si la lista está vacía.
     */
    private function seleccionar(string $modelo, ?array $ids, string $campoFecha)
    {
        if (! empty($ids)) {
            $items = $modelo::whereIn('id', $ids)->get();
            // Conservar el orden en que el admin los seleccionó
            return $items->sortBy(fn ($i) => array_search($i->id, $ids))->values();
        }

        return $modelo::orderByDesc($campoFecha)->take(3)->get();
    }
}
