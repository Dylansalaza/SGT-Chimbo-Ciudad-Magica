<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    // Permitir guardar estos datos de forma masiva
    protected $fillable = ['ip_address', 'user_agent', 'url_visited'];
}