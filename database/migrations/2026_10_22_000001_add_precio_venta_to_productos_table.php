<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Precio de venta unitario, sin IVA. El stock NO se guarda aquí: se calcula con los lotes.
            $table->decimal('precio_venta', 12, 2)->default(0)->after('unidad_medida');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('precio_venta');
        });
    }
};
