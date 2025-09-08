<?php

namespace App\Observers;

use App\Models\AssetItem;
use App\Models\AssetDepreciation;
use Illuminate\Support\Facades\Log;

class AssetItemObserver
{
    /**
     * Handle the AssetItem "created" event.
     */
    public function created(AssetItem $assetItem): void
    {
        // Only create depreciation record if asset has a value
        if ($assetItem->original_value && $assetItem->original_value > 0) {
            try {
                $this->createInitialDepreciation($assetItem);
                Log::info("Auto-created depreciation record for asset item {$assetItem->id}");
            } catch (\Exception $e) {
                Log::error("Failed to auto-create depreciation for asset item {$assetItem->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the AssetItem "updated" event.
     */
    public function updated(AssetItem $assetItem): void
    {
        // If original value was changed, update depreciation record
        if ($assetItem->wasChanged('original_value')) {
            try {
                $this->updateDepreciationForValueChange($assetItem);
                Log::info("Updated depreciation record for asset item {$assetItem->id} due to value change");
            } catch (\Exception $e) {
                Log::error("Failed to update depreciation for asset item {$assetItem->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the AssetItem "deleted" event.
     */
    public function deleted(AssetItem $assetItem): void
    {
        // Depreciation records will be automatically deleted due to cascade
        Log::info("Asset item {$assetItem->id} deleted, depreciation records cleaned up");
    }

    /**
     * Create initial depreciation record for a new asset
     */
    private function createInitialDepreciation(AssetItem $assetItem): void
    {
        // Check if depreciation record already exists
        if ($assetItem->depreciation()->exists()) {
            return;
        }

        // Get default settings from config or use defaults
        $usefulLifeYears = config('asset-depreciation.default_useful_life_years', 5);
        $salvageValue = config('asset-depreciation.default_salvage_value', 0);
        $method = config('asset-depreciation.default_method', AssetDepreciation::METHOD_STRAIGHT_LINE);

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
            'depreciation_start_date' => now(),
            'last_calculated_date' => now(),
            'metadata' => [
                'created_by' => 'system',
                'created_at' => now()->toISOString(),
                'auto_created' => true,
                'source' => 'observer',
            ],
        ]);
    }

    /**
     * Update depreciation when asset value changes
     */
    private function updateDepreciationForValueChange(AssetItem $assetItem): void
    {
        $depreciation = $assetItem->depreciation()->latest()->first();
        
        if (!$depreciation) {
            // Create new depreciation record if none exists
            $this->createInitialDepreciation($assetItem);
            return;
        }

        // Update the depreciation record with new values
        $oldValue = $assetItem->getOriginal('original_value');
        $newValue = $assetItem->original_value;
        
        // Recalculate depreciation based on new value
        $depreciableAmount = $newValue - $depreciation->salvage_value;
        $annualDepreciation = $depreciableAmount / $depreciation->useful_life_years;
        
        $depreciation->update([
            'original_value' => $newValue,
            'depreciation_rate' => $annualDepreciation,
            'metadata' => array_merge($depreciation->metadata ?? [], [
                'value_updated_at' => now()->toISOString(),
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ]),
        ]);

        // Recalculate current value
        $depreciation->calculateDepreciation();
    }
}
