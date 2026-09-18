<?php

use App\Enums\TipoComprobante;
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
        Schema::table('gastos', function (Blueprint $table) {
            $table->string('proveedor', 150)->nullable()->after('descripcion');
            $table->date('fecha_compra')->nullable()->after('proveedor');
            $table->enum('tipo_comprobante', array_column(TipoComprobante::cases(), 'value'))->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->enum('tipo_comprobante', ['boleta', 'factura', 'declaracion_jurada'])->change();
            $table->dropColumn(['proveedor', 'fecha_compra']);
        });
    }
};
