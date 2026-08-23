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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jass_id')->constrained('jass')->restrictOnDelete();
            $table->foreignId('socio_id')->constrained('socios')->restrictOnDelete();
            // Siempre presente, incluido mantenimiento (8.6): el mantenimiento
            // se cobra por vivienda, no por socio.
            $table->foreignId('vivienda_id')->constrained('viviendas')->restrictOnDelete();
            $table->foreignId('medidor_id')->nullable()->constrained('medidores')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->enum('tipo', ['mantenimiento', 'consumo_medidor', 'consumo_directo']);
            $table->unsignedInteger('numero_recibo');
            $table->char('periodo', 7);
            $table->string('concepto', 60);
            $table->decimal('lectura_anterior', 10, 2)->nullable();
            $table->decimal('lectura_actual', 10, 2)->nullable();
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->timestamps();

            $table->unique(['jass_id', 'numero_recibo']);
            $table->index(['vivienda_id', 'tipo', 'periodo']);
            $table->index('medidor_id');
            $table->index(['jass_id', 'fecha_pago']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
