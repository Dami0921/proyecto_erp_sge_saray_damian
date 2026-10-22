<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->string('numero_orden', 20)->unique();          // OC-00001
            // Nullable: permite registrar compras sin el módulo Proveedores
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores');
            $table->foreignId('user_id')->constrained('users');    // quien creó la orden
            $table->date('fecha');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('iva', 14, 2);
            $table->decimal('total', 14, 2);
            // pendiente -> parcial -> recibida (o anulada si no llegó nada)
            $table->string('estado', 20)->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamp('recibida_at')->nullable();          // cuándo se completó la recepción
            $table->timestamps();
            $table->softDeletes();                                 // una orden anulada se conserva
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
