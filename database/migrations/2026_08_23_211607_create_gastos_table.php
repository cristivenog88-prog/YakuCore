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
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jass_id')->constrained('jass')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('descripcion', 255);
            $table->decimal('monto', 10, 2);
            $table->enum('tipo_comprobante', ['boleta', 'factura', 'declaracion_jurada']);
            $table->string('foto_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('jass_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
