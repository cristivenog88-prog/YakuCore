<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Informe de Gestión Financiera (PDF) — código canónico
 * "INFORME N.° NNNN-YYYY-JASS" por año y JASS. Se genera a partir de una
 * Rendición aprobada. Uno por rendición.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('jass_id')->constrained('jass')->cascadeOnDelete();
            $table->foreignId('rendicion_id')->unique()->constrained('rendiciones')->cascadeOnDelete();
            $table->unsignedInteger('numero'); // correlativo por año
            $table->year('anio');
            $table->string('pdf_path')->nullable();
            $table->timestamp('generado_at')->nullable();
            $table->foreignId('generado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['jass_id', 'anio', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informes');
    }
};
