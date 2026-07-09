<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Igual que la migración que vinculó galerías a eventos: agrega una columna
 * "news_id" opcional para que las fotos/videos subidos a una noticia también
 * se reflejen automáticamente en su propia entrada de la galería pública.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->foreignId('news_id')->nullable()->after('event_id')->constrained('news')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->dropForeign(['news_id']);
            $table->dropColumn('news_id');
        });
    }
};
