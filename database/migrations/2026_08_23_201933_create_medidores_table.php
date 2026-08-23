<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medidores', function (Blueprint $table) {
            $table->id();
            // jass_id denormalizada (mismo motivo que viviendas, sección 2).
            $table->foreignId('jass_id')->constrained('jass')->restrictOnDelete();
            $table->foreignId('vivienda_id')->constrained('viviendas')->restrictOnDelete();
            $table->unsignedInteger('correlativo');
            $table->string('codigo', 50)->unique();
            $table->decimal('lectura_inicial', 10, 2)->default(0);
            $table->dateTime('reiniciado_en')->nullable();
            $table->boolean('activo')->default(true);
            $table->date('fecha_instalacion')->nullable();
            $table->timestamps();

            $table->unique(['jass_id', 'correlativo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medidores');
    }
};
