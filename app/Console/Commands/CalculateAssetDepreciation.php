<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AssetItem;
use App\Models\AssetDepreciation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CalculateAssetDepreciation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:calculate-depreciation 
                            {--force : Force recalculation of all assets}
                            {--date= : Calculate depreciation as of specific date (YYYY-MM-DD)}
                            {--asset-id= : Calculate depreciation for specific asset only}
                            {--dry-run : Show what would be calculated without making changes}
                            {--chunk-size=1000 : Number of assets to process in each chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate depreciation for all assets or specific asset';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting asset depreciation calculation...');
        
        $asOfDate = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::now();
        $forceRecalculation = $this->option('force');
        $assetId = $this->option('asset-id');
        $dryRun = $this->option('dry-run');
        $chunkSize = $this->option('chunk-size');
        
        $this->info("Calculating depreciation as of: " . $asOfDate->format('Y-m-d'));
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        try {
            if ($assetId) {
                // Process single asset
                $this->info("Processing single asset ID: {$assetId}");
                $asset = AssetItem::find($assetId);
                if ($asset) {
                    $result = $this->processAssetDepreciation($asset, $asOfDate, $forceRecalculation, $dryRun);
                    if ($result['processed']) {
                        $this->info("Asset {$assetId} processed successfully!");
                    } else {
                        $this->warn("Asset {$assetId} skipped: " . $result['error']);
                    }
                } else {
                    $this->error("Asset ID {$assetId} not found!");
                    return 1;
                }
            } else {
                // Process all assets in chunks via queue
                $this->processInChunksViaQueue($chunkSize, $asOfDate, $forceRecalculation, $dryRun);
            }
            
        } catch (\Exception $e) {
            $this->error('Fatal error during depreciation calculation: ' . $e->getMessage());
            Log::error('Fatal error in depreciation calculation: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Process assets in chunks via queue
     */
    private function processInChunksViaQueue(int $chunkSize, Carbon $asOfDate, bool $forceRecalculation, bool $dryRun): void
    {
        // Get total count for progress tracking
        $totalAssets = AssetItem::count();
        $this->info("Total assets to process: {$totalAssets}");
        $this->info("Processing in chunks of {$chunkSize} via queue...");
        
        $chunkCount = 0;
        $totalQueued = 0;
        
        // Get asset IDs in chunks
        AssetItem::select('id')
            ->chunk($chunkSize, function($chunk) use (&$chunkCount, &$totalQueued, $chunkSize, $asOfDate, $forceRecalculation, $dryRun) {
                $assetIds = $chunk->pluck('id')->toArray();
                
                // Dispatch job for this chunk
                \App\Jobs\CalculateAssetDepreciationJob::dispatch($assetIds, $asOfDate->format('Y-m-d'), $forceRecalculation, $dryRun);
                
                $chunkCount++;
                $totalQueued += count($assetIds);
                $this->info("Queued chunk #{$chunkCount} with " . count($assetIds) . " assets (Total queued: {$totalQueued})");
            });
        
        $this->info("All {$chunkCount} chunks have been queued for processing!");
        $this->info("Total assets queued: {$totalQueued}");
        $this->info("Monitor progress with: php artisan queue:work");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - Jobs were queued but will not make changes');
        }
    }
    
    /**
     * Process depreciation for a single asset item
     */
    private function processAssetDepreciation(AssetItem $assetItem, Carbon $asOfDate, bool $forceRecalculation, bool $dryRun): array
    {
        // Skip if asset has no original value
        if (!$assetItem->original_value || $assetItem->original_value <= 0) {
            return [
                'processed' => false,
                'action' => 'skipped',
                'error' => 'No original value'
            ];
        }
        
        // Ensure we have only one depreciation record and get it
        $latestDepreciation = $assetItem->ensureSingleDepreciationRecord();
        
        // Check if we need to calculate depreciation
        if (!$latestDepreciation->wasRecentlyCreated) {
            // Record already existed, check if update is needed
            $lastCalculated = $latestDepreciation->last_calculated_date;
            $daysSinceLastCalculation = $lastCalculated ? $asOfDate->diffInDays($lastCalculated) : 0;
            
            // Recalculate if:
            // 1. Force recalculation is requested
            // 2. It's been more than 30 days since last calculation
            // 3. The as-of date is different from last calculated date
            if ($forceRecalculation || $daysSinceLastCalculation > 30 || 
                ($lastCalculated && $asOfDate->format('Y-m-d') !== $lastCalculated->format('Y-m-d'))) {
                
                if (!$dryRun) {
                    $this->updateDepreciation($latestDepreciation, $asOfDate);
                }
                
                return [
                    'processed' => true,
                    'action' => 'updated',
                    'error' => null
                ];
            }
            
            return [
                'processed' => false,
                'action' => 'skipped',
                'error' => 'No recalculation needed'
            ];
        } else {
            // New record was created, now calculate depreciation
            if (!$dryRun) {
                $this->updateDepreciation($latestDepreciation, $asOfDate);
            }
            return [
                'processed' => true,
                'action' => 'created',
                'error' => null
            ];
        }
        

    }
    
    /**
     * Create initial depreciation record for an asset
     */
    private function createInitialDepreciation(AssetItem $assetItem, Carbon $asOfDate): void
    {
        // Get configurable values from config or use sensible defaults
        $usefulLifeYears = config('asset-depreciation.default_useful_life_years', 5);
        $salvageValue = config('asset-depreciation.default_salvage_value', 0);
        $method = config('asset-depreciation.default_method', AssetDepreciation::METHOD_STRAIGHT_LINE);
        
        // Check if asset has category-specific overrides
        if ($assetItem->asset_category_id) {
            $categoryOverrides = config('asset-depreciation.category_overrides', []);
            $categoryName = $assetItem->assetCategory->name ?? null;
            
            if ($categoryName && isset($categoryOverrides[strtolower($categoryName)])) {
                $overrides = $categoryOverrides[strtolower($categoryName)];
                $usefulLifeYears = $overrides['useful_life_years'] ?? $usefulLifeYears;
                $salvageValue = $overrides['salvage_value'] ?? $salvageValue;
            }
        }
        
        // Calculate initial depreciation rate
        $depreciableAmount = $assetItem->original_value - $salvageValue;
        $annualDepreciation = $depreciableAmount / $usefulLifeYears;
        
        AssetDepreciation::create([
            'asset_item_id' => $assetItem->id,
            'original_value' => $assetItem->original_value,
            'salvage_value' => $salvageValue,
            'useful_life_years' => $usefulLifeYears,
            'depreciation_method' => $method,
            'depreciation_rate' => $annualDepreciation,
            'current_value' => $assetItem->original_value,
            'accumulated_depreciation' => 0,
            'depreciation_start_date' => $asOfDate,
            'last_calculated_date' => $asOfDate,
            'metadata' => [
                'created_by' => 'system',
                'created_at' => now()->toISOString(),
                'auto_created' => true,
                'config_source' => 'console_command',
                'useful_life_years' => $usefulLifeYears,
                'salvage_value' => $salvageValue,
            ],
        ]);
    }
    
    /**
     * Update existing depreciation record
     */
    private function updateDepreciation(AssetDepreciation $depreciation, Carbon $asOfDate): void
    {
        // Calculate depreciation up to the as-of date
        $depreciation->calculateDepreciation($asOfDate);
    }
}
