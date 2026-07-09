<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de un lugar turístico (tabla tourist_places).
 * Campo clave: "activo" (boolean, default true) — controla si el lugar se
 * muestra en el sitio público (mapa, home, búsqueda IA) o está "dado de baja"
 * desde el panel admin, sin borrar el registro de la base de datos.
 */
class TouristPlace extends Model
{
    // Campos que se pueden asignar en masa (create()/update() con arrays de datos)
    protected $fillable = [
        'nombre', 'categoria', 'descripcion', 'lat', 'lng',
        'direccion', 'telefono', 'horario', 'precio', 'imagen_url', 'destacado', 'galeria', 'activo'
    ];

    // Conversión automática de tipos al leer/escribir estos atributos
    protected $casts = [
        'destacado' => 'boolean', // Si aparece en "Lugares Destacados" del Home
        'galeria'   => 'array',   // Fotos adicionales (además de imagen_url), guardadas como JSON
        'activo'    => 'boolean', // false = "dado de baja", oculto del sitio público
    ];

    /** Imágenes de referencia usadas por el motor CLIP para reconocer este lugar. */
    public function referenceImages()
    {
        return $this->hasMany(ReferenceImage::class);
    }
}