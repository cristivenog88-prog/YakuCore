<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade la categoría de POA (nullable) al gasto para poder agregar la
 * ejecución trimestral por clasificación en la Rendición formal
 * (R.M. N.° 205-2010-VIVIENDA §). Los gastos existentes quedan sin
 * categorizar (NULL) — el reporte los muestra como "sin categoría".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->enum('categoria', [
                'operacion',
                'mantenimiento',
                'administracion',
                'reposicion_equipos',
                'rehabilitaciones_menores',
            ])->nullable()->after('monto');
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });
    }
};
