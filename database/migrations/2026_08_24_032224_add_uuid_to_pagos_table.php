<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * El recibo (spec 5.9) se enlaza por UUID, no por el id autoincremental
     * de `pagos` — evita que la URL revele el correlativo real de recibos
     * emitidos ni permita enumerarlos cambiando el número (Pago::getRouteKeyName).
     */
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('pagos')->select('id')->orderBy('id')->get()->each(function (object $fila): void {
            DB::table('pagos')->where('id', $fila->id)->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
