<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Identidad corporativa de la empresa que usa el ERP: una sola fila.
    public function up(): void
    {
        Schema::create('organizacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('nit', 30)->nullable();
            $table->string('direccion', 160)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('eslogan', 120)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('color', 20)->default('verde');
            // El logo se guarda como data URI (png/jpg ≤ 300 KB): así viaja con la base de datos y no depende de storage:link
            $table->longText('logo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizacion');
    }
};
