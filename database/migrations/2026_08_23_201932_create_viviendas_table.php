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
        Schema::create('viviendas', function (Blueprint $table) {
            $table->id();
            // jass_id denormalizada a propósito (clave de scoping multi-tenant,
            // sección 2 del plan): la consistencia con socio.jass_id la
            // garantiza el servicio de creación, no una FK compuesta.
            $table->foreignId('jass_id')->constrained('jass')->restrictOnDelete();
            $table->foreignId('socio_id')->constrained('socios')->restrictOnDelete();
            $table->string('direccion', 180);
            $table->boolean('activa')->default(true);
            $table->date('morosa_desde')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('jass_id');
            $table->index('socio_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viviendas');
    }
};
