<?php

namespace App\Services;

use App\Contracts\ImageSearchInterface;
use Illuminate\Support\Facades\Http;

/**
 * Implementación concreta del motor de búsqueda visual basada en el
 * microservicio Flask + CLIP (clip_service.py).
 *
 * Encapsula TODA la comunicación HTTP con el servicio de IA (SRP). El
 * controller ya no conoce URLs, timeouts ni el formato de la API de Flask:
 * solo conversa con la abstracción ImageSearchInterface.
 */
class FlaskImageSearchService implements ImageSearchInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeout = 30,
        private readonly ?string $token = null,
    ) {
    }

    /** Cabeceras con el token compartido (solo si está configurado). */
    private function headers(): array
    {
        return $this->token ? ['X-CLIP-Token' => $this->token] : [];
    }

    public function search(string $imageBase64): array
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders($this->headers())
            ->post("{$this->baseUrl}/search", ['image' => $imageBase64]);

        return $response->json('matches', []);
    }

    public function refreshIndex(): array
    {
        $response = Http::timeout(max($this->timeout, 45))
            ->withHeaders($this->headers())
            ->post("{$this->baseUrl}/refresh");

        return $response->json() ?? ['success' => false, 'error' => 'Respuesta vacía del motor.'];
    }

    public function health(): array
    {
        $response = Http::timeout(10)->get("{$this->baseUrl}/health");

        return $response->json() ?? ['status' => 'offline'];
    }
}
