<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_settings', function (Blueprint $table) {
            // Qué secciones mostrar en el Home: {"destacados":true,"noticias":true,"eventos":true}
            $table->json('secciones')->nullable()->after('carousel');
            // IDs de noticias/eventos elegidos para el Home (vacío = los más recientes)
            $table->json('noticias_ids')->nullable()->after('secciones');
            $table->json('eventos_ids')->nullable()->after('noticias_ids');
        });
    }

    public function down(): void
    {
        Schema::table('home_settings', function (Blueprint $table) {
            $table->dropColumn(['secciones', 'noticias_ids', 'eventos_ids']);
        });
    }
};
