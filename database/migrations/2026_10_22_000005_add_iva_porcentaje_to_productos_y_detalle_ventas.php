<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// El IVA deja de ser fijo: cada producto define su porcentaje (0 = no lleva IVA)
// y cada línea de venta guarda el porcentaje que se aplicó en ese momento.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('iva_porcentaje', 5, 2)->default(19)->after('precio_venta');
        });

        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->decimal('iva_porcentaje', 5, 2)->default(19)->after('precio_unitario');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropColumn('iva_porcentaje');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('iva_porcentaje');
        });
    }
};
