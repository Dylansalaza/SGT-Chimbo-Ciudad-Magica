<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Agrega la columna "activo" a tourist_places (feature "dar de baja").
return new class extends Migration
{
    /** Aplica el cambio: agrega la columna con default true (todos los lugares existentes quedan activos). */
    public function up()
    {
        Schema::table('tourist_places', function (Blueprint $table) {
            // Permite "dar de baja" un lugar sin borrarlo de la base de datos:
            // deja de mostrarse al público (mapa, búsqueda IA) pero el admin
            // conserva el registro y puede reactivarlo cuando quiera.
            $table->boolean('activo')->default(true)->after('galeria');
        });
    }

    /** Revierte el cambio: quita la columna (usado por "php artisan migrate:rollback"). */
    public function down()
    {
        Schema::table('tourist_places', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
