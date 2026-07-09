<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ejecutar con: php artisan migrate
 * 
 * Agrega las columnas que el worker Python necesita para guardar
 * el score de confianza y los candidatos similares.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('image_searches', function (Blueprint $table) {
            // Score del mejor match devuelto por CLIP (0.0 - 1.0)
            $table->float('top_score')->nullable()->after('tourist_place_id');

            // JSON con los top-5 candidatos: [{"id":1,"score":0.87}, ...]
            $table->text('candidates')->nullable()->after('top_score');
        });
    }

    public function down(): void
    {
        Schema::table('image_searches', function (Blueprint $table) {
            $table->dropColumn(['top_score', 'candidates']);
        });
    }
};