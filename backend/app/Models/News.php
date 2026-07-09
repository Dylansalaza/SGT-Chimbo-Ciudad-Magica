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
}