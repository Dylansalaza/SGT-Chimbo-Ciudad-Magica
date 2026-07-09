<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chat_faqs', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');          // palabra clave (ej. 'clima', 'evento')
            $table->text('answer');             // respuesta asociada
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_faqs');
    }
};