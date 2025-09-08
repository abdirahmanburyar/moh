<?php

namespace App\Observers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Assignee;
use App\Models\AssetLocation;
use App\Models\SubLocation;
use App\Models\Region;
use App\Models\AssetCategory;
use App\Models\AssetType;
use App\Models\FundSource;

class AssetObserver
{
    /**
     * Handle the Asset "created" event.
     */
    public function created(Asset $asset): void
    {
        // Record initial status for all asset items
        if (!empty($asset->status)) {
            $asset->createHistory([
                'action' => 'asset_created',
                'action_type' => 'creation',
                'notes' => 'Asset created'
            ]);
        }
    }

    /**
     * Handle the Asset "updated" event.
     */
    public function updated(Asset $asset): void
    {
        // Deduplicate by looking for identical recent asset_history entries
        $now = now();

        // Status change
        if ($asset->wasChanged('status')) {
            // Get asset item IDs to check for recent history
            $assetItemIds = $asset->assetItems()->pluck('id');
            
            $recent = AssetHistory::whereIn('asset_item_id', $assetItemIds)
                ->where('action', 'status_changed')
                ->where('action_type', 'status_change')
                ->where('performed_at', '>=', $now->copy()->subMinute())
                ->where('new_value->status', $asset->status)
                ->exists();
            if (!$recent) {
                $asset->createHistory([
                    'action' => 'status_changed',
                    'action_type' => 'status_change',
                    'old_value' => ['status' => $asset->getOriginal('status')],
                    'new_value' => ['status' => $asset->status],
                    'notes' => 'Status updated'
                ]);
            }
        }


        if ($asset->wasChanged('sub_location_id')) {
            $oldId = $asset->getOriginal('sub_location_id');
            $newId = $asset->sub_location_id;
            $oldValues['sub_location'] = ['id' => $oldId, 'name' => optional(SubLocation::find($oldId))->name];
            $newValues['sub_location'] = ['id' => $newId, 'name' => optional(SubLocation::find($newId))->name];
        }

        if ($asset->wasChanged('asset_category_id')) {
            $oldId = $asset->getOriginal('asset_category_id');
            $newId = $asset->asset_category_id;
            $oldValues['category'] = ['id' => $oldId, 'name' => optional(AssetCategory::find($oldId))->name];
            $newValues['category'] = ['id' => $newId, 'name' => optional(AssetCategory::find($newId))->name];
        }

        if ($asset->wasChanged('type_id')) {
            $oldId = $asset->getOriginal('type_id');
            $newId = $asset->type_id;
            $oldValues['type'] = ['id' => $oldId, 'name' => optional(AssetType::find($oldId))->name];
            $newValues['type'] = ['id' => $newId, 'name' => optional(AssetType::find($newId))->name];
        }

        if ($asset->wasChanged('fund_source_id')) {
            $oldId = $asset->getOriginal('fund_source_id');
            $newId = $asset->fund_source_id;
            $oldValues['fund_source'] = ['id' => $oldId, 'name' => optional(FundSource::find($oldId))->name];
            $newValues['fund_source'] = ['id' => $newId, 'name' => optional(FundSource::find($newId))->name];
        }


    }
}
