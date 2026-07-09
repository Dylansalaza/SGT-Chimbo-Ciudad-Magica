<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->nullable(); // Guarda la IP del visitante
            $table->text('user_agent')->nullable();      // Guarda el navegador/dispositivo
            $table->string('url_visited')->nullable();     // Qué página vio
            $table->timestamps();                        // Registra fecha y hora exacta
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};