<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Visit extends Model
{
    // Permitir guardar estos datos de forma masiva
    protected $fillable = ['ip_address', 'user_agent', 'url_visited'];

    // Al registrar una visita, invalida la caché pública de /api/stats (5 min,
    // ver StatsController) para que el número que ve el visitante en el sitio
    // público no se quede desfasado del número (en vivo) del panel admin.
    protected static function booted(): void
    {
        static::created(function () {
            Cache::forget('stats_publicas_' . now()->toDateString());
        });
    }
}