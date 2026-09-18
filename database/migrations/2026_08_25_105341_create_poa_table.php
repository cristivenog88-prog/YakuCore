<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Un POA (Plan Operativo Anual) por JASS y año (unique). `otros_ingresos`
     * agrupa saldos del año anterior y multas: se resta del presupuesto para
     * no inflar la cuota (paso 2 de la guía POA).
     */
    public function up(): void
    {
        Schema::create('poa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jass_id')->constrained('jass')->restrictOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->decimal('otros_ingresos', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['jass_id', 'anio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poa');
    }
};
