<?php

namespace App\Contracts;

/**
 * Contrato del motor de búsqueda visual (CLIP).
 *
 * Permite aplicar el principio de Inversión de Dependencias (SOLID-D): los
 * controllers dependen de esta abstracción y no de la implementación concreta
 * (hoy Flask). Cambiar de motor —p. ej. a un microservicio gRPC o a otro
 * proveedor— solo requiere una nueva clase que implemente esta interfaz
 * (patrón Strategy), sin tocar el controller.
 */
interface ImageSearchInterface
{
    /**
     * Envía una imagen en base64 y obtiene los IDs emparejados con sus puntajes.
     *
     * @return array<int, array{id:int, score:float}>
     */
    public function search(string $imageBase64): array;

    /**
     * Ordena al motor recargar/reindexar el catálogo de imágenes.
     *
     * @return array{success:bool, vectores_indexados?:int, error?:string}
     */
    public function refreshIndex(): array;

    /**
     * Estado de salud del motor (liveness/readiness probe).
     *
     * @return array<string, mixed>
     */
    public function health(): array;
}
