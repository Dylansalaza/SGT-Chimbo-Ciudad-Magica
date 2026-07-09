<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceImage extends Model
{
    protected $fillable = ['tourist_place_id', 'image_url'];
    
    public function touristPlace()
    {
        return $this->belongsTo(TouristPlace::class);
    }
}