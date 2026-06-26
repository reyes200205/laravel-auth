<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Office;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear Oficinas
        $officeUtt = Office::create([
            'name' => 'UTT',
            'latitude' => 25.53060477270776,
            'longitude' => -103.32148524907628,
            'radius' => 1200, // Aumentado a 1200 metros para permitir el acceso
            'allowed_ips' => '127.0.0.1',
        ]);

        $officeMasterDrilling = Office::create([
            'name' => 'Master Drilling',
            'latitude' => 25.6044566,
            'longitude' => -103.3870974,
            'radius' => 1000,
            'allowed_ips' => '127.0.0.1',
        ]);

        $officeCasa = Office::create([
            'name' => 'Casa',
            'latitude' => 25.6005072,
            'longitude' => -103.4151497,
            'radius' => 500, // 250 metros de margen
            'allowed_ips' => '127.0.0.1',
        ]);




        //===========================================================//
        //usuarios UTT

        // Crear Usuarios Administradores (Super Admins)
        $uttAdmin = User::create([
            'name' => 'Alessandro (UTT)',
            'email' => 'sifuentesmarcelo78@gmail.com',
            'password' => Hash::make('Miri_ta?'),
            'office_id' => $officeUtt->id,
        ]);
        
        $uttAdmin->assignRole('super-admin');



        //=================================================================//
        // usuarios master
        $adminMaster = User::create([
            'name' => 'alejandro (Master Drilling)',
            'email' => 'renteriareyesjorgealejandro4@gmail.com',
            'password' => Hash::make('Reyes221119?'),
            'office_id' => $officeMasterDrilling->id,
        ]);

        $adminMaster->assignRole('super-admin');


        // ============================================================//

        // ==========================================================================// 
        // usuarios casa 
        $homeAdmin = User::create([
            'name' => 'Alejandro (CASA)',
            'email' => 'reyes221119@gmail.com',
            'password' => Hash::make('Reyes221119?'),
            'office_id' => $officeCasa->id,
        ]);
        $homeAdmin->assignRole('super-admin');



        $NormalUserCasa = User::create([
            'name' => 'Alejandro',
            'email' => 'jorgerenteriareyes4@gmail.com',
            'office_id' => $officeCasa->id,
            'password' => Hash::make('Reyes221119?'),
        ]);
        $NormalUserCasa->assignRole('user');

        $NormalUserUtt = User::create([
            'name' => 'Igmar Salazar',
            'email' => 'alereyes221119@gmail.com',
            'password' => Hash::make('Reyes221119?'),
            'office_id' => $officeUtt->id
        ]);

        $NormalUserUtt->assignRole('user');
    }
}
