<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
   protected $fillable = ['title', 'categoria', 'body', 'published_at', 'image_url', 'images'];
    protected $casts = [
        'published_at' => 'datetime',
        'images'       => 'array',
    ];

    // Al crear/editar/borrar una noticia, la caché de /home queda obsoleta.
    protected static function booted(): void
    {
        $olvidar = fn () => \App\Http\Controllers\HomeController::olvidarCache();
        static::saved($olvidar);
        static::deleted($olvidar);
    }
}