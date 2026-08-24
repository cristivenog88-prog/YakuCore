<?php

namespace Database\Seeders;

use App\Models\Jass;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos demo: 1 superadmin + 2 JASS (una con medidor, otra sin) con
     * presidente/tesorero/vocal cada una. Credenciales documentadas en
     * README.md — todas usan la contraseña "password".
     */
    public function run(): void
    {
        User::factory()->superadmin()->create([
            'name' => 'Administrador YakuCore',
            'email' => 'superadmin@yakucore.pe',
        ]);

        $jassConMedidor = Jass::factory()->conMedidor()->create([
            'nombre' => 'Villa Sol',
            'comunidad' => 'Villa Sol',
            'distrito' => 'Independencia',
            'periodicidad_mantenimiento' => 'mensual',
            'dia_vencimiento' => 15,
        ]);

        User::factory()->presidente($jassConMedidor)->create([
            'name' => 'Presidente Villa Sol',
            'email' => 'presidente.villasol@yakucore.pe',
        ]);

        User::factory()->tesorero($jassConMedidor)->create([
            'name' => 'Tesorero Villa Sol',
            'email' => 'tesorero.villasol@yakucore.pe',
        ]);

        User::factory()->vocal($jassConMedidor)->create([
            'name' => 'Vocal Villa Sol',
            'email' => 'vocal.villasol@yakucore.pe',
        ]);

        $jassSinMedidor = Jass::factory()->sinMedidor()->create([
            'nombre' => 'Los Andes',
            'comunidad' => 'Los Andes',
            'distrito' => 'Huaraz',
            'periodicidad_mantenimiento' => 'mensual',
            'dia_vencimiento' => 15,
        ]);

        User::factory()->presidente($jassSinMedidor)->create([
            'name' => 'Presidente Los Andes',
            'email' => 'presidente.losandes@yakucore.pe',
        ]);

        User::factory()->tesorero($jassSinMedidor)->create([
            'name' => 'Tesorero Los Andes',
            'email' => 'tesorero.losandes@yakucore.pe',
        ]);

        User::factory()->vocal($jassSinMedidor)->create([
            'name' => 'Vocal Los Andes',
            'email' => 'vocal.losandes@yakucore.pe',
        ]);

        $this->call(DemoDataSeeder::class);
    }
}
