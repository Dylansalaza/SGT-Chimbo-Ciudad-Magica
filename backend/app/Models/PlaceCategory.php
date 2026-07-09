<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaceCategory extends Model
{
    protected $table = 'place_categories';

    protected $fillable = ['nombre', 'icono'];
}
