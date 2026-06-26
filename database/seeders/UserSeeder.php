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
        // Crear u obtener Oficinas
        $officeUtt = Office::updateOrCreate(
            ['name' => 'UTT'],
            [
                'latitude' => 25.53060477270776,
                'longitude' => -103.32148524907628,
                'radius' => 1200,
                'allowed_ips' => '127.0.0.1',
            ]
        );

        $officeMasterDrilling = Office::updateOrCreate(
            ['name' => 'Master Drilling'],
            [
                'latitude' => 25.6044566,
                'longitude' => -103.3870974,
                'radius' => 1000,
                'allowed_ips' => '127.0.0.1',
            ]
        );

        $officeCasa = Office::updateOrCreate(
            ['name' => 'Casa'],
            [
                'latitude' => 25.6005072,
                'longitude' => -103.4151497,
                'radius' => 500,
                'allowed_ips' => '127.0.0.1',
            ]
        );

        //===========================================================//
        //usuarios UTT

        // Crear Usuarios Administradores (Super Admins)
        $uttAdmin = User::updateOrCreate(
            ['email' => 'sifuentesmarcelo78@gmail.com'],
            [
                'name' => 'Alessandro (UTT)',
                'password' => Hash::make('Miri_ta?'),
                'office_id' => $officeUtt->id,
            ]
        );
        if (!$uttAdmin->hasRole('super-admin')) {
            $uttAdmin->assignRole('super-admin');
        }

        //=================================================================//
        // usuarios master
        $adminMaster = User::updateOrCreate(
            ['email' => 'renteriareyesjorgealejandro4@gmail.com'],
            [
                'name' => 'alejandro (Master Drilling)',
                'password' => Hash::make('Reyes221119?'),
                'office_id' => $officeMasterDrilling->id,
            ]
        );
        if (!$adminMaster->hasRole('super-admin')) {
            $adminMaster->assignRole('super-admin');
        }

        // ============================================================//

        // ==========================================================================// 
        // usuarios casa 
        $homeAdmin = User::updateOrCreate(
            ['email' => 'reyes221119@gmail.com'],
            [
                'name' => 'Alejandro (CASA)',
                'password' => Hash::make('Reyes221119?'),
                'office_id' => $officeCasa->id,
            ]
        );
        if (!$homeAdmin->hasRole('super-admin')) {
            $homeAdmin->assignRole('super-admin');
        }

        $NormalUserCasa = User::updateOrCreate(
            ['email' => 'jorgerenteriareyes4@gmail.com'],
            [
                'name' => 'Alejandro',
                'office_id' => $officeCasa->id,
                'password' => Hash::make('Reyes221119?'),
            ]
        );
        if (!$NormalUserCasa->hasRole('user')) {
            $NormalUserCasa->assignRole('user');
        }

        $NormalUserUtt = User::updateOrCreate(
            ['email' => 'alereyes221119@gmail.com'],
            [
                'name' => 'Igmar Salazar',
                'password' => Hash::make('Reyes221119?'),
                'office_id' => $officeUtt->id
            ]
        );
        if (!$NormalUserUtt->hasRole('user')) {
            $NormalUserUtt->assignRole('user');
        }
    }
}
