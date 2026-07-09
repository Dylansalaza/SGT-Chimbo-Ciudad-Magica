<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('admin_role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('admin_role_id')->constrained('admin_roles')->onDelete('cascade');
            $table->primary(['user_id', 'admin_role_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_role_user');
        Schema::dropIfExists('admin_roles');
    }
};