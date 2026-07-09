<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tourist_places', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('categoria');
            $table->text('descripcion');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('direccion');
            $table->string('telefono')->nullable();
            $table->string('horario')->nullable();
            $table->string('precio')->nullable();
            $table->string('imagen_url');
            $table->json('galeria')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tourist_places');
    }
};