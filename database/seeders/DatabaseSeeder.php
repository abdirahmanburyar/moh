<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,    // Create all permissions first
            UserSeeder::class,          // Create Super Admin user with all permissions
            SomaliRegionsDistrictsSeeder::class, // Create Somali regions and districts
        ]);
    }
}
