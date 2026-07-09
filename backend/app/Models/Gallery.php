<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    // "event_id"/"news_id" vinculan opcionalmente esta galería a un Evento o
    // una Noticia (nunca ambos a la vez): así, al subir fotos/videos en esos
    // formularios, se sincronizan automáticamente aquí sin que el admin
    // tenga que subirlas dos veces.
    protected $fillable = ['event_id', 'news_id', 'title', 'category', 'images'];
    protected $casts = ['images' => 'array'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function news()
    {
        return $this->belongsTo(News::class);
    }
}