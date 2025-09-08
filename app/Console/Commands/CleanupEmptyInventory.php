<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\InventoryItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupEmptyInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:cleanup-empty {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete InventoryItems with quantity = 0 and delete Inventories that have no items left';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('DRY RUN MODE - No actual deletions will be performed');
        }

        $this->info('Starting inventory cleanup...');

        try {
            DB::beginTransaction();

            // Step 1: Find and delete InventoryItems with quantity = 0
            $emptyItems = InventoryItem::where('quantity', 0)->get();
            $emptyItemsCount = $emptyItems->count();

            $this->info("Found {$emptyItemsCount} inventory items with quantity = 0");

            if ($emptyItemsCount > 0) {
                if (!$isDryRun) {
                    // Delete the empty items
                    InventoryItem::where('quantity', 0)->delete();
                    $this->info("Deleted {$emptyItemsCount} empty inventory items");
                } else {
                    $this->info("Would delete {$emptyItemsCount} empty inventory items");
                    
                    // Show some examples
                    $this->line("Examples of items that would be deleted:");
                    $emptyItems->take(5)->each(function ($item) {
                        $this->line("  - Item ID: {$item->id}, Batch: {$item->batch_number}, Expiry: {$item->expiry_date}");
                    });
                }
            }

            // Step 2: Find and delete Inventories that have no items left
            $inventoriesWithoutItems = Inventory::whereDoesntHave('items')->get();
            $inventoriesWithoutItemsCount = $inventoriesWithoutItems->count();

            $this->info("Found {$inventoriesWithoutItemsCount} inventories with no items");

            if ($inventoriesWithoutItemsCount > 0) {
                if (!$isDryRun) {
                    // Delete inventories that have no items
                    Inventory::whereDoesntHave('items')->delete();
                    $this->info("Deleted {$inventoriesWithoutItemsCount} empty inventories");
                } else {
                    $this->info("Would delete {$inventoriesWithoutItemsCount} empty inventories");
                    
                    // Show some examples
                    $this->line("Examples of inventories that would be deleted:");
                    $inventoriesWithoutItems->take(5)->each(function ($inventory) {
                        $this->line("  - Inventory ID: {$inventory->id}, Product: {$inventory->product->name}");
                    });
                }
            }

            // Step 3: Also check for inventories where all items have quantity = 0
            $inventoriesWithOnlyEmptyItems = Inventory::whereHas('items', function ($query) {
                $query->where('quantity', '>', 0);
            }, '<', 1)->whereHas('items')->get();
            
            $inventoriesWithOnlyEmptyItemsCount = $inventoriesWithOnlyEmptyItems->count();

            $this->info("Found {$inventoriesWithOnlyEmptyItemsCount} inventories where all items have quantity = 0");

            if ($inventoriesWithOnlyEmptyItemsCount > 0) {
                if (!$isDryRun) {
                    // Delete all items from these inventories first
                    $totalItemsDeleted = 0;
                    foreach ($inventoriesWithOnlyEmptyItems as $inventory) {
                        $itemsCount = $inventory->items()->count();
                        $inventory->items()->delete();
                        $totalItemsDeleted += $itemsCount;
                    }
                    
                    // Then delete the inventories
                    $inventoryIds = $inventoriesWithOnlyEmptyItems->pluck('id')->toArray();
                    Inventory::whereIn('id', $inventoryIds)->delete();
                    
                    $this->info("Deleted {$totalItemsDeleted} items and {$inventoriesWithOnlyEmptyItemsCount} inventories with only empty items");
                } else {
                    $this->info("Would delete inventories with only empty items");
                    
                    // Show some examples
                    $this->line("Examples of inventories that would be deleted:");
                    $inventoriesWithOnlyEmptyItems->take(5)->each(function ($inventory) {
                        $this->line("  - Inventory ID: {$inventory->id}, Product: {$inventory->product->name}, Items: {$inventory->items->count()}");
                    });
                }
            }

            if (!$isDryRun) {
                DB::commit();
                $this->info('Inventory cleanup completed successfully!');
            } else {
                DB::rollBack();
                $this->info('Dry run completed. No changes were made.');
            }

            // Summary
            $totalDeleted = $emptyItemsCount + $inventoriesWithoutItemsCount + $inventoriesWithOnlyEmptyItemsCount;
            if ($totalDeleted > 0) {
                $this->info("Total items/inventories that would be affected: {$totalDeleted}");
            } else {
                $this->info("No cleanup needed - all inventories are in good condition!");
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during inventory cleanup: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}
