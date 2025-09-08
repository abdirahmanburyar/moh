<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SomaliRegionsDistrictsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // First, create all regions
        $regions = [
            'Sanaag',
            'Sool',
            'Bari',
            'Nugaal',
            'Mudug'
        ];

        foreach ($regions as $regionName) {
            DB::table('regions')->insertOrIgnore([
                'name' => $regionName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Note: Districts table was removed as part of facilities module cleanup
        // Only regions are seeded now

        $this->command->info('Somali regions seeded successfully!');
        $this->command->info('Created ' . count($regions) . ' regions.');
    }
}
