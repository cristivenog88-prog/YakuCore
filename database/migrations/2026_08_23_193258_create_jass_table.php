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
        Schema::create('jass', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->string('prefijo', 30)->unique();
            $table->string('comunidad', 120);
            $table->string('distrito', 120);
            $table->boolean('tiene_medidor');
            $table->decimal('tarifa_base_m3', 8, 2)->nullable();
            $table->enum('periodicidad_mantenimiento', ['mensual', 'trimestral', 'semestral', 'anual']);
            $table->unsignedTinyInteger('dia_vencimiento')->default(15);
            $table->unsignedInteger('ultimo_numero_recibo')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jass');
    }
};
