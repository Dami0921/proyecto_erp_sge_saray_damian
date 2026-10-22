<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Desacopla Productos de Proveedores: el producto puede existir sin proveedor
// y, si se borra un proveedor, el producto se conserva (proveedor_id queda en NULL).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
        });

        DB::statement('ALTER TABLE productos MODIFY proveedor_id BIGINT UNSIGNED NULL');

        Schema::table('productos', function (Blueprint $table) {
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
        });

        DB::statement('ALTER TABLE productos MODIFY proveedor_id BIGINT UNSIGNED NOT NULL');

        Schema::table('productos', function (Blueprint $table) {
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('cascade');
        });
    }
};
