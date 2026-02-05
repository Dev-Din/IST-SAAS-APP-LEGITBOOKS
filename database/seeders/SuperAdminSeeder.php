<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['owner', 'subadmin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'admin']);
        }

        $admin = Admin::updateOrCreate(
            ['email' => 'admin@legitbooks.com'],
            [
                'name' => 'Owner',
                'password' => 'password',
                'role' => 'owner',
                'is_active' => true,
            ]
        );

        $admin->syncRoles(['owner']);
    }
}
