<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla `events`.
     *
     * NOTA: anteriormente esta migración usaba Schema::table() (ALTER) sobre una
     * tabla que nunca llegaba a crearse, por lo que `php artisan migrate` fallaba
     * y la aplicación completa quedaba inservible. Ahora crea la tabla de verdad.
     * La columna `image_url` se añade en una migración posterior.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('location')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
