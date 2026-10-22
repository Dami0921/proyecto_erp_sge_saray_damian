<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->unsignedInteger('cantidad');                        // pedida
            $table->unsignedInteger('cantidad_recibida')->default(0);   // acumulada (recepciones parciales)
            $table->decimal('costo_unitario', 12, 2);
            $table->decimal('iva_porcentaje', 5, 2)->default(19);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();

            // Un producto no puede repetirse dentro de la misma orden
            $table->unique(['compra_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_compras');
    }
};
