<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'buryar313@gmail.com'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email' => 'buryar313@gmail.com',
                'password' => Hash::make('password'),
                'title' => 'Super Administrator',
                'is_active' => true,
            ]
        );

        // Get all permissions
        $permissions = Permission::all();

        // Assign all permissions to Super Admin user
        $superAdmin->permissions()->sync($permissions->pluck('id'));

        $this->command->info('Super Admin user created successfully!');
        $this->command->info('Email: buryar313@gmail.com');
        $this->command->info('Password: password');
        $this->command->info('Assigned ' . $permissions->count() . ' permissions');
    }
}
