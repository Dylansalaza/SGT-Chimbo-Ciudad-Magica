<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tourist_places', function (Blueprint $table) {
            // Marca para mostrar el lugar en la sección "Destacados" del Home.
            $table->boolean('destacado')->default(false)->after('imagen_url');
        });
    }

    public function down(): void
    {
        Schema::table('tourist_places', function (Blueprint $table) {
            $table->dropColumn('destacado');
        });
    }
};
