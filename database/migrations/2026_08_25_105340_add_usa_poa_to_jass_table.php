<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `usa_poa` solo tiene sentido cuando `tiene_medidor` es false (el
     * superadmin lo pregunta en ese caso al dar de alta la JASS): si la
     * JASS decide elaborar su Plan Operativo Anual, la cuota de
     * mantenimiento se calcula desde el POA en vez de ingresarse a mano.
     * Nullable porque una JASS con medidor no responde esta pregunta.
     */
    public function up(): void
    {
        Schema::table('jass', function (Blueprint $table) {
            $table->boolean('usa_poa')->nullable()->after('tiene_medidor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jass', function (Blueprint $table) {
            $table->dropColumn('usa_poa');
        });
    }
};
