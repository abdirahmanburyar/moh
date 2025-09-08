<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\WarehouseAmc;
use Illuminate\Console\Command;

class GenerateRandomAmc extends Command
{
    protected $signature = 'warehouse:generate-random-amc';
    protected $description = 'Generate random AMC data for the last 5 months';

    public function handle()
    {
        $this->info('Starting random AMC data generation...');

        // Get the last 5 months
        $months = collect(range(1, 5))->map(function($monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            return [
                'month_year' => $date->format('Y-m'),
                'start_date' => $date->startOfMonth()->format('Y-m-d'),
                'end_date' => $date->endOfMonth()->format('Y-m-d'),
            ];
        });

        // Process each product
        Product::chunk(100, function ($products) use ($months) {
            foreach ($products as $product) {
                foreach ($months as $month) {
                    // Generate random quantity between 1000 and 5000
                    $randomQuantity = rand(1000, 5000);

                    // Create or update AMC record
                    WarehouseAmc::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'month_year' => $month['month_year']
                        ],
                        [
                            'quantity' => $randomQuantity
                        ]
                    );

                    $this->info("Generated AMC for product: {$product->name} - Month: {$month['month_year']} - Quantity: {$randomQuantity}");
                }
            }
        });

        $this->info('Random AMC data generation completed successfully!');
    }
}
