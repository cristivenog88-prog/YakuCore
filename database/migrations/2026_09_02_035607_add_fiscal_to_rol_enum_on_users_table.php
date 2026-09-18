<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Añade 'fiscal' al enum de `users.rol` (necesario para la doble firma
 * Tesorero+Fiscal de la Conciliación mensual y la Rendición trimestral).
 *
 * Se usa SQL crudo con ALTER TABLE MODIFY porque Doctrine DBAL no soporta
 * cambios de valores dentro de un ENUM de MySQL de forma limpia; el mismo
 * enfoque se aplica en down() para revertir sin perder los usuarios existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return; // sqlite (tests) no usa enums nativos
        }

        DB::statement("ALTER TABLE users MODIFY rol ENUM('superadmin','presidente','tesorero','fiscal','vocal') NOT NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Reasigna cualquier fiscal existente a vocal antes de restringir el enum
        DB::table('users')->where('rol', 'fiscal')->update(['rol' => 'vocal']);

        DB::statement("ALTER TABLE users MODIFY rol ENUM('superadmin','presidente','tesorero','vocal') NOT NULL");
    }
};
