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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('jass_id')->nullable()->after('id')->constrained('jass')->restrictOnDelete();
            $table->enum('rol', ['superadmin', 'presidente', 'tesorero', 'vocal'])->after('jass_id');
            $table->softDeletes();

            $table->index('jass_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['jass_id']);
            $table->dropIndex(['jass_id']);
            $table->dropColumn(['jass_id', 'rol', 'deleted_at']);
        });
    }
};
