<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->restrictOnDelete();
            $table->enum('tipo', ['entrada', 'salida']);
            // inventario_inicial, compra, venta, anulacion_venta, ajuste, baja_vencimiento
            $table->string('concepto', 30);
            $table->unsignedInteger('cantidad'); // siempre positiva; el tipo indica el sentido
            $table->integer('saldo'); // existencias físicas del PRODUCTO (todos sus lotes) después del movimiento
            $table->decimal('costo_unitario', 12, 2)->nullable(); // costo (compras) o precio (ventas) de referencia
            $table->nullableMorphs('referencia'); // documento que lo originó (Venta, Compra) o nada (ajuste)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('observaciones', 255)->nullable(); // motivo obligatorio en los ajustes
            $table->timestamps();

            $table->index(['producto_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
