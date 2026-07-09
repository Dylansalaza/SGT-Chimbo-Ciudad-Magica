<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            // Galería de imágenes adicionales (la portada sigue siendo image_url).
            $table->json('images')->nullable()->after('image_url');
            // Categoría para filtrar (Política, Cultura, Deportes, etc.).
            $table->string('categoria')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn(['images', 'categoria']);
        });
    }
};
