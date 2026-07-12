<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'categoria',
        'description',
        'starts_at',
        'ends_at',
        'location',
        'image_url',
        'images',
    ];

    protected $casts = [
    'starts_at' => 'datetime',
    'ends_at' => 'datetime',
    'location' => 'array',
    'images' => 'array',
    ];

    public function gallery()
    {
        return $this->hasOne(Gallery::class);
    }

    // Al crear/editar/borrar un evento, la caché de /home queda obsoleta.
    protected static function booted(): void
    {
        $olvidar = fn () => \App\Http\Controllers\HomeController::olvidarCache();
        static::saved($olvidar);
        static::deleted($olvidar);
    }
}