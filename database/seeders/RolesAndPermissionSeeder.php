<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roles = [
            'super-admin',
            'user', 
            'guest',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $superAdminRole = Role::findByName('super-admin');
        $superAdminRole->givePermissionTo($permissions);

        $userRole = Role::findByName('user');
        $userRole->givePermissionTo([
            'users.view',
            'users.edit',
        ]);

        $guestRole = Role::findByName('guest');
        $guestRole->givePermissionTo([
            'users.view',
        ]);
    }
}
