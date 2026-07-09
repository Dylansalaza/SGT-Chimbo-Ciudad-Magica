<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('icono')->nullable()->default('📍');
            $table->timestamps();
        });

        // Categorías iniciales migradas desde el select estático
        $categorias = [
            ['nombre' => 'Hotel',        'icono' => '🏨'],
            ['nombre' => 'Hostal',       'icono' => '🛏️'],
            ['nombre' => 'Hostería',     'icono' => '🏡'],
            ['nombre' => 'Cascada',      'icono' => '💧'],
            ['nombre' => 'Mirador',      'icono' => '🔭'],
            ['nombre' => 'Parque',       'icono' => '🌳'],
            ['nombre' => 'Iglesia',      'icono' => '⛪'],
            ['nombre' => 'Laguna',       'icono' => '🌊'],
            ['nombre' => 'Restaurante',  'icono' => '🍽️'],
            ['nombre' => 'Cafetería',    'icono' => '☕'],
            ['nombre' => 'Bar',          'icono' => '🍺'],
        ];

        foreach ($categorias as $cat) {
            DB::table('place_categories')->insert(array_merge($cat, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('place_categories');
    }
};
