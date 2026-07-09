<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageSearch extends Model
{
    use HasFactory;

    // Le indicamos explícitamente el nombre de tu tabla
    protected $table = 'image_searches';

    // Habilitamos la asignación masiva de tus variables
    protected $fillable = [
        'image_path',
        'status',
        'tourist_place_id',
        'error_message'
    ];

    /**
     * Relación opcional con tu tabla de lugares turísticos
     */
    public function touristPlace()
    {
        return $this->belongsTo(TouristPlace::class, 'tourist_place_id');
    }
}