<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder {
    public function run(): void {
        $admin = User::firstOrCreate(
            ['email'=>'admin@fintech.local'],
            ['name'=>'System Admin','password'=>bcrypt('Admin@123')]
        );
        if (! $admin->hasRole('admin')) $admin->assignRole('admin');
    }
}
