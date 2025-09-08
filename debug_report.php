<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\MonthlyInventoryReport;
use App\Models\Product;
use App\Models\Warehouse;

echo "Checking database tables...\n";

// Check if tables exist
$tables = ['monthly_inventory_reports', 'monthly_quantity_receiveds', 'issue_quantity_reports', 'inventory_adjustments'];
foreach ($tables as $table) {
    $exists = DB::getSchemaBuilder()->hasTable($table);
    echo "Table {$table} exists: " . ($exists ? 'Yes' : 'No') . "\n";
}

// Check data in tables
echo "\nData in tables:\n";
echo "monthly_quantity_receiveds count: " . DB::table('monthly_quantity_receiveds')->count() . "\n";
echo "issue_quantity_reports count: " . DB::table('issue_quantity_reports')->count() . "\n";

// Check if any monthly reports have been generated
$reports = MonthlyInventoryReport::all();
echo "\nMonthly inventory reports count: " . $reports->count() . "\n";

if ($reports->count() > 0) {
    echo "\nSample reports:\n";
    foreach ($reports->take(3) as $report) {
        echo "ID: {$report->id}, Month: {$report->month_year}, Product: {$report->product_id}, Warehouse: {$report->warehouse_id}\n";
        echo "Beginning: {$report->beginning_balance}, Received: {$report->stock_received}, Issued: {$report->stock_issued}\n";
        echo "Closing: {$report->closing_balance}\n\n";
    }
}

// Check products and warehouses
echo "\nProducts count: " . Product::count() . "\n";
echo "Warehouses count: " . Warehouse::count() . "\n";

// Check if there's any inventory data
$inventoryCount = DB::table('inventories')->count();
echo "\nInventory records count: " . $inventoryCount . "\n";

if ($inventoryCount > 0) {
    $sampleInventory = DB::table('inventories')->first();
    echo "Sample inventory: " . json_encode($sampleInventory) . "\n";
}

echo "\nDone.\n";
