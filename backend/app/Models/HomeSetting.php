<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSetting extends Model
{
    protected $table = 'home_settings';

    protected $fillable = [
        'welcome_title',
        'welcome_text',
        'carousel',
        'secciones',
        'noticias_ids',
        'eventos_ids',
    ];

    protected $casts = [
        'carousel'     => 'array',
        'secciones'    => 'array',
        'noticias_ids' => 'array',
        'eventos_ids'  => 'array',
    ];

    /**
     * Devuelve la (única) fila de configuración, creándola con valores por
     * defecto si todavía no existe. Patrón "singleton" a nivel de tabla.
     */
    public static function singleton(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'welcome_title' => 'San José de Chimbo',
                'welcome_text'  => 'Fundada por Sebastián de Benalcázar en 1535 y elevada a cantón el 3 de marzo de 1860, San José de Chimbo es la cabecera cantonal del cantón Chimbo, en la provincia de Bolívar. Situada a 2450 msnm en un repliegue de la Cordillera Occidental de los Andes, a solo 17 km de Guaranda, es reconocida a nivel nacional por su tradición artesanal en talabartería y guitarras.',
                'carousel'      => [],
            ]
        );
    }
}
