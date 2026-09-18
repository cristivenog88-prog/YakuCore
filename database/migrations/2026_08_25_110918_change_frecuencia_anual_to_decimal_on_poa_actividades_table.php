<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * La guía POA usa "cantidad" no siempre entera (ej. "57.5 kg de cloro
     * al año"), no solo un conteo de repeticiones — unsignedSmallInteger se
     * queda corto.
     */
    public function up(): void
    {
        Schema::table('poa_actividades', function (Blueprint $table) {
            $table->decimal('frecuencia_anual', 8, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poa_actividades', function (Blueprint $table) {
            $table->unsignedSmallInteger('frecuencia_anual')->change();
        });
    }
};
