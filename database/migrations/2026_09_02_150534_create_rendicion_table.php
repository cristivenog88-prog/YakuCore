<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rendición trimestral formal (R.M. N.° 205-2010-VIVIENDA): consolida
 * ingresos y egresos del trimestre, ejecución por categoría de POA, y
 * requiere checklist documental completo + fecha de asamblea antes de
 * aprobarse. Una rendición por JASS y trimestre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rendiciones', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('jass_id')->constrained('jass')->cascadeOnDelete();
            $table->foreignId('poa_id')->nullable()->constrained('poa')->nullOnDelete();
            $table->unsignedTinyInteger('trimestre'); // 1..4
            $table->year('anio');
            $table->date('fecha_asamblea')->nullable();
            $table->decimal('saldo_inicial', 12, 2)->default(0);
            $table->decimal('ingresos_pagos', 12, 2)->default(0);
            $table->decimal('otros_ingresos', 12, 2)->default(0);
            $table->json('ejecucion_por_categoria')->nullable();
            $table->decimal('egresos_total', 12, 2)->default(0);
            $table->decimal('saldo_final', 12, 2)->default(0);
            $table->json('checklist')->nullable();
            $table->enum('estado', ['borrador', 'aprobada'])->default('borrador');
            $table->timestamp('aprobada_at')->nullable();
            $table->string('numero_acta', 100)->nullable();
            $table->foreignId('aprobada_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['jass_id', 'anio', 'trimestre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rendiciones');
    }
};
