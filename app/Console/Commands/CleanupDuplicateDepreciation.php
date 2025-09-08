<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AssetItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupDuplicateDepreciation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:cleanup-depreciation 
                            {--dry-run : Show what would be cleaned up without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up duplicate depreciation records for assets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        $this->info('Starting depreciation cleanup...');
        
        try {
            $duplicateCount = 0;
            $cleanedCount = 0;
            
            // Find assets with multiple depreciation records
            $assetsWithDuplicates = AssetItem::whereHas('depreciation', function($query) {
                $query->havingRaw('COUNT(*) > 1');
            })->with('depreciation')->get();
            
            $this->info("Found {$assetsWithDuplicates->count()} assets with potential duplicate depreciation records");
            
            foreach ($assetsWithDuplicates as $assetItem) {
                $depreciationRecords = $assetItem->depreciation()->orderBy('created_at')->get();
                
                if ($depreciationRecords->count() > 1) {
                    $duplicateCount++;
                    $this->info("Asset {$assetItem->id} has {$depreciationRecords->count()} depreciation records");
                    
                    if (!$dryRun) {
                        // Keep the first (oldest) record and delete the rest
                        $mainRecord = $depreciationRecords->first();
                        $recordsToDelete = $depreciationRecords->where('id', '!=', $mainRecord->id);
                        
                        $this->info("  - Keeping record {$mainRecord->id} (created: {$mainRecord->created_at})");
                        $this->info("  - Deleting " . $recordsToDelete->count() . " duplicate records");
                        
                        foreach ($recordsToDelete as $record) {
                            $this->info("    - Deleting record {$record->id} (created: {$record->created_at})");
                        }
                        
                        // Delete duplicate records
                        $recordsToDelete->each(function($record) {
                            $record->delete();
                        });
                        
                        $cleanedCount++;
                        
                        Log::info("Cleaned up duplicate depreciation records for asset {$assetItem->id}", [
                            'kept_record_id' => $mainRecord->id,
                            'deleted_count' => $recordsToDelete->count(),
                            'deleted_record_ids' => $recordsToDelete->pluck('id')->toArray()
                        ]);
                    }
                }
            }
            
            if ($dryRun) {
                $this->warn("DRY RUN: Would clean up {$duplicateCount} assets with duplicate depreciation records");
            } else {
                $this->info("Cleanup completed successfully!");
                $this->info("Assets processed: {$duplicateCount}");
                $this->info("Assets cleaned: {$cleanedCount}");
            }
            
        } catch (\Exception $e) {
            $this->error('Error during cleanup: ' . $e->getMessage());
            Log::error('Error during depreciation cleanup: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
