<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_factura', 20)->unique();      // FV-00001
            // Nullable: permite vender a "consumidor final" y que Ventas funcione sin el módulo Clientes
            $table->foreignId('cliente_id')->nullable()->constrained('clientes');
            $table->foreignId('user_id')->constrained('users');  // vendedor que registró la venta
            $table->date('fecha');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('iva', 14, 2);
            $table->decimal('total', 14, 2);
            $table->string('estado', 20)->default('completada'); // completada | anulada
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();                               // una venta anulada se conserva
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
