<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Tablas cuyas rutas usan un {parametro} en la URL y hasta ahora
     * enlazaban por el id autoincremental (spec 7.1): expone el correlativo
     * real de registros y permite enumerarlos cambiando el número. `jass`
     * es la más expuesta — sus rutas de portal público no requieren sesión.
     */
    private const TABLAS = ['jass', 'socios', 'viviendas', 'medidores', 'gastos', 'reuniones', 'anuncios', 'users'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->uuid('uuid')->nullable()->after('id');
            });

            DB::table($tabla)->select('id')->orderBy('id')->get()->each(function (object $fila) use ($tabla): void {
                DB::table($tabla)->where('id', $fila->id)->update(['uuid' => (string) Str::uuid()]);
            });

            Schema::table($tabla, function (Blueprint $table): void {
                $table->uuid('uuid')->nullable(false)->unique()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->dropColumn('uuid');
            });
        }
    }
};
