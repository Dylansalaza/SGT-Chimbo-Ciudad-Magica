<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contenido editable del Home de React (una sola fila).
     * Guardamos el carrusel y los textos como JSON para máxima flexibilidad.
     */
    public function up(): void
    {
        Schema::create('home_settings', function (Blueprint $table) {
            $table->id();
            $table->string('welcome_title')->default('San José de Chimbo');
            $table->text('welcome_text')->nullable();
            // Carrusel: [{ "url": "...", "title": "...", "subtitle": "..." }, ...]
            $table->json('carousel')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_settings');
    }
};
