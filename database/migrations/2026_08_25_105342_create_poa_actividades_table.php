<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Actividades del POA (paso 1 de la guía): qué se hará, con qué
     * frecuencia anual, a qué costo unitario, en qué mes y quién es el
     * responsable. `jass_id` denormalizado (mismo criterio que viviendas y
     * medidores, sección 2 del plan) para que PerteneceAJass pueda
     * escoparlas sin depender de un join contra `poa`.
     */
    public function up(): void
    {
        Schema::create('poa_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jass_id')->constrained('jass')->restrictOnDelete();
            $table->foreignId('poa_id')->constrained('poa')->cascadeOnDelete();
            $table->enum('categoria', ['operacion', 'mantenimiento', 'administracion', 'reposicion_equipos', 'rehabilitaciones_menores']);
            $table->string('descripcion', 180);
            $table->unsignedSmallInteger('frecuencia_anual');
            $table->decimal('costo_unitario', 10, 2);
            $table->string('mes', 60)->nullable();
            $table->string('responsable', 120)->nullable();
            $table->timestamps();

            $table->index('poa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poa_actividades');
    }
};
