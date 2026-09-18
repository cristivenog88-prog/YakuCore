<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conciliación financiera mensual (R.M. N.° 205-2010-VIVIENDA): compara los
 * pagos registrados vs. el efectivo/banco verificado por Tesorero y Fiscal.
 * Doble firma obligatoria (spec §8 — regla de gobernanza).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conciliaciones', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('jass_id')->constrained('jass')->cascadeOnDelete();
            $table->char('periodo', 7); // YYYY-MM
            $table->decimal('pagos_total', 12, 2)->default(0);
            $table->decimal('gastos_total', 12, 2)->default(0);
            $table->decimal('saldo_calculado', 12, 2)->default(0);
            $table->decimal('efectivo_verificado', 12, 2)->default(0);
            $table->decimal('banco_verificado', 12, 2)->default(0);
            $table->decimal('fondos_verificados', 12, 2)->default(0);
            $table->decimal('diferencia', 12, 2)->default(0);
            $table->enum('estado', ['conciliado', 'revisar'])->default('revisar');
            $table->foreignId('tesorero_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('fiscal_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('firmado_tesorero_at')->nullable();
            $table->timestamp('firmado_fiscal_at')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['jass_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conciliaciones');
    }
};
