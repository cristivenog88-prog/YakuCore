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
        Schema::create('socios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jass_id')->constrained('jass')->restrictOnDelete();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->char('dni', 8);
            $table->string('telefono', 15)->nullable();
            $table->string('direccion', 180);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['jass_id', 'apellidos']);
            $table->unique(['jass_id', 'dni']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('socios');
    }
};
