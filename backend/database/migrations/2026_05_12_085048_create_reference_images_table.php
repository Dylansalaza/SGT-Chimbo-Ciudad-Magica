<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reference_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tourist_place_id')->constrained()->onDelete('cascade');
            $table->string('image_url');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reference_images');
    }
};