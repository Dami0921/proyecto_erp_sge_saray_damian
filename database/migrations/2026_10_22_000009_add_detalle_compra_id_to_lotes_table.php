<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Trazabilidad: cada lote que nace de una recepción queda ligado a la línea de compra de origen
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->foreignId('detalle_compra_id')->nullable()->after('costo_unitario')
                ->constrained('detalle_compras')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('detalle_compra_id');
        });
    }
};
