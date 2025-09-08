<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AssetItem;
use App\Models\AssetDepreciation;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CalculateAssetDepreciationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3; // Retry 3 times if failed

    protected $asOfDate;
    protected $assetIds;
    protected $forceRecalculation;
    protected $dryRun;

    /**
     * Create a new job instance.
     */
    public function __construct(array $assetIds = [], ?string $asOfDate = null, bool $forceRecalculation = false, bool $dryRun = false)
    {
        $this->asOfDate = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();
        $this->assetIds = $assetIds;
        $this->forceRecalculation = $forceRecalculation;
        $this->dryRun = $dryRun;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting background depreciation calculation job', [
            'as_of_date' => $this->asOfDate->format('Y-m-d'),
            'asset_ids_count' => count($this->assetIds),
            'force_recalculation' => $this->forceRecalculation,
            'dry_run' => $this->dryRun
        ]);

        try {
            if (empty($this->assetIds)) {
                // Process all assets (existing behavior)
                $this->processAllAssets();
            } else {
                // Process specific chunk of assets
                $this->processAssetChunk();
            }
            
        } catch (\Exception $e) {
            Log::error('Fatal error in background depreciation calculation: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Process all assets (existing behavior)
     */
    private function processAllAssets(): void
    {
        $assets = AssetItem::with(['depreciation' => function($q) {
            $q->latest();
        }])->get();
        
        $this->processAssets($assets);
    }
    
    /**
     * Process specific chunk of assets
     */
    private function processAssetChunk(): void
    {
        $assets = AssetItem::whereIn('id', $this->assetIds)
            ->with(['depreciation' => function($q) {
                $q->latest();
            }])->get();
        
        $this->processAssets($assets);
    }
    
    /**
     * Process a collection of assets
     */
    private function processAssets($assets): void
    {
        $processedCount = 0;
        $updatedCount = 0;
        $createdCount = 0;
        
        foreach ($assets as $assetItem) {
            try {
                $result = $this->processAssetDepreciation($assetItem);
                
                if ($result['processed']) {
                    $processedCount++;
                    
                    if ($result['action'] === 'updated') {
                        $updatedCount++;
                    } elseif ($result['action'] === 'created') {
                        $createdCount++;
                    }
                }
                
            } catch (\Exception $e) {
                Log::error("Depreciation calculation error for asset {$assetItem->id}: " . $e->getMessage());
            }
        }
        
        Log::info('Background depreciation calculation completed', [
            'processed_count' => $processedCount,
            'updated_count' => $updatedCount,
            'created_count' => $createdCount,
            'total_assets' => $assets->count()
        ]);
    }
    
    /**
     * Process depreciation for a single asset item
     */
    private function processAssetDepreciation(AssetItem $assetItem): array
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
            $daysSinceLastCalculation = $lastCalculated ? $this->asOfDate->diffInDays($lastCalculated) : 0;
            
            // Recalculate if:
            // 1. Force recalculation is requested
            // 2. It's been more than 30 days since last calculation
            // 3. The as-of date is different from last calculated date
            if ($this->forceRecalculation || $daysSinceLastCalculation > 30 || 
                ($lastCalculated && $this->asOfDate->format('Y-m-d') !== $lastCalculated->format('Y-m-d'))) {
                
                $this->updateDepreciation($latestDepreciation);
                
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
            $this->updateDepreciation($latestDepreciation);
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
    private function createInitialDepreciation(AssetItem $assetItem): void
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
            'depreciation_start_date' => $this->asOfDate,
            'last_calculated_date' => $this->asOfDate,
            'metadata' => [
                'created_by' => 'system',
                'created_at' => now()->toISOString(),
                'auto_created' => true,
                'job_id' => $this->job->getJobId(),
                'config_source' => 'background_job',
                'useful_life_years' => $usefulLifeYears,
                'salvage_value' => $salvageValue,
            ],
        ]);
    }
    
    /**
     * Update existing depreciation record
     */
    private function updateDepreciation(AssetDepreciation $depreciation): void
    {
        // Calculate depreciation up to the as-of date
        $depreciation->calculateDepreciation($this->asOfDate);
    }
}
