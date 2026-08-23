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
        Schema::create('reuniones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jass_id')->constrained('jass')->restrictOnDelete();
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();
            $table->date('fecha');
            $table->time('hora');
            $table->string('lugar', 180);
            $table->text('puntos_tratar')->nullable();
            $table->enum('estado', ['programada', 'cancelada', 'finalizada'])->default('programada');
            $table->timestamps();

            $table->index('jass_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reuniones');
    }
};
