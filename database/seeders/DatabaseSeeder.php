<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles if they don't exist
        $adminRole    = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $merchantRole = Role::firstOrCreate(['name' => 'merchant', 'guard_name' => 'api']);
        $userRole     = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        // Create or update admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'], // unique identifier
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
            ]
        );

        // Assign role if not already assigned
        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }
    }
}
