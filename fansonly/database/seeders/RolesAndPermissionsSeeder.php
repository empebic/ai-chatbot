<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['admin', 'creator', 'user'];
        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName);
        }

        $permissions = [
            'create posts',
            'view subscribers posts',
            'tip',
            'subscribe',
            'manage own profile',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm);
        }

        Role::findByName('creator')->givePermissionTo(['create posts', 'manage own profile']);
        Role::findByName('user')->givePermissionTo(['subscribe', 'tip', 'view subscribers posts']);
        Role::findByName('admin')->givePermissionTo(Permission::all());
    }
}
