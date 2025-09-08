<?php

namespace App\Http\Controllers;

use App\Models\AssetItem;
use App\Models\AssetDepreciation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetDepreciationController extends Controller
{
    public function index(Request $request)
    {
        $depreciation = AssetDepreciation::with(['assetItem.asset'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->whereHas('assetItem', function ($q) use ($request) {
                    $q->where('asset_name', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->filled('depreciation_method'), function ($query) use ($request) {
                $query->where('depreciation_method', $request->depreciation_method);
            })
            ->when($request->filled('asset_item_id'), function ($query) use ($request) {
                $query->where('asset_item_id', $request->asset_item_id);
            })
            ->when($request->filled('value_from'), function ($query) use ($request) {
                $query->where('current_value', '>=', $request->value_from);
            })
            ->when($request->filled('value_to'), function ($request) use ($query) {
                $query->where('current_value', '<=', $request->value_to);
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->where('depreciation_start_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->where('depreciation_start_date', '<=', $request->date_to);
            })
            ->orderBy('depreciation_start_date', 'desc')
            ->paginate(15);

        return Inertia::render('AssetDepreciation/Index', [
            'depreciation' => $depreciation,
            'filters' => $request->only(['search', 'depreciation_method', 'asset_item_id', 'value_from', 'value_to', 'date_from', 'date_to']),
            'assetItems' => AssetItem::select('id', 'asset_name')->get(),
            'depreciationMethods' => AssetDepreciation::getDepreciationMethods(),
        ]);
    }

    public function create()
    {
        return Inertia::render('AssetDepreciation/Create', [
            'assetItems' => AssetItem::select('id', 'asset_name', 'original_value')->get(),
            'depreciationMethods' => AssetDepreciation::getDepreciationMethods(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_item_id' => 'required|exists:asset_items,id',
            'original_value' => 'required|numeric|min:0',
            'salvage_value' => 'required|numeric|min:0|lte:original_value',
            'useful_life_years' => 'required|integer|min:1|max:100',
            'depreciation_method' => 'required|string|in:' . implode(',', array_keys(AssetDepreciation::getDepreciationMethods())),
            'depreciation_start_date' => 'required|date',
        ]);

        // Check if asset item already has depreciation record
        $existingDepreciation = AssetDepreciation::where('asset_item_id', $request->asset_item_id)->first();
        if ($existingDepreciation) {
            return back()->withErrors(['asset_item_id' => 'This asset item already has a depreciation record.']);
        }

        $assetItem = AssetItem::findOrFail($request->asset_item_id);
        
        // Calculate initial depreciation rate based on method
        $depreciationRate = $this->calculateInitialDepreciationRate(
            $request->original_value,
            $request->salvage_value,
            $request->useful_life_years,
            $request->depreciation_method
        );

        $depreciation = AssetDepreciation::create([
            'asset_item_id' => $request->asset_item_id,
            'original_value' => $request->original_value,
            'salvage_value' => $request->salvage_value,
            'useful_life_years' => $request->useful_life_years,
            'depreciation_method' => $request->depreciation_method,
            'depreciation_rate' => $depreciationRate,
            'current_value' => $request->original_value,
            'accumulated_depreciation' => 0,
            'depreciation_start_date' => $request->depreciation_start_date,
            'last_calculated_date' => now(),
            'metadata' => [
                'created_by' => auth()->id(),
                'created_at' => now()->toISOString(),
            ],
        ]);

        return redirect()->route('asset.depreciation.index')
            ->with('success', 'Depreciation record created successfully.');
    }

    public function show(AssetDepreciation $depreciation)
    {
        $depreciation->load(['assetItem.asset']);
        
        return Inertia::render('AssetDepreciation/Show', [
            'depreciation' => $depreciation,
        ]);
    }

    public function edit(AssetDepreciation $depreciation)
    {
        $depreciation->load(['assetItem.asset']);
        
        return Inertia::render('AssetDepreciation/Edit', [
            'depreciation' => $depreciation,
            'assetItems' => AssetItem::select('id', 'asset_name', 'original_value')->get(),
            'depreciationMethods' => AssetDepreciation::getDepreciationMethods(),
        ]);
    }

    public function update(Request $request, AssetDepreciation $depreciation)
    {
        $request->validate([
            'asset_item_id' => 'required|exists:asset_items,id',
            'original_value' => 'required|numeric|min:0',
            'salvage_value' => 'required|numeric|min:0|lte:original_value',
            'useful_life_years' => 'required|integer|min:1|max:100',
            'depreciation_method' => 'required|string|in:' . implode(',', array_keys(AssetDepreciation::getDepreciationMethods())),
            'depreciation_start_date' => 'required|date',
        ]);

        // Recalculate depreciation rate
        $depreciationRate = $this->calculateInitialDepreciationRate(
            $request->original_value,
            $request->salvage_value,
            $request->useful_life_years,
            $request->depreciation_method
        );

        $depreciation->update([
            'asset_item_id' => $request->asset_item_id,
            'original_value' => $request->original_value,
            'salvage_value' => $request->salvage_value,
            'useful_life_years' => $request->useful_life_years,
            'depreciation_method' => $request->depreciation_method,
            'depreciation_rate' => $depreciationRate,
            'depreciation_start_date' => $request->depreciation_start_date,
        ]);

        // Recalculate current values
        $depreciation->recalculateDepreciation();

        return redirect()->route('asset.depreciation.index')
            ->with('success', 'Depreciation record updated successfully.');
    }

    public function destroy(AssetDepreciation $depreciation)
    {
        $depreciation->delete();

        return redirect()->route('asset.depreciation.index')
            ->with('success', 'Depreciation record deleted successfully.');
    }

    public function recalculate(AssetDepreciation $depreciation)
    {
        $depreciation->recalculateDepreciation();

        return redirect()->route('asset.depreciation.index')
            ->with('success', 'Depreciation recalculated successfully.');
    }

    public function bulkRecalculate(Request $request)
    {
        $request->validate([
            'asset_item_ids' => 'required|array',
            'asset_item_ids.*' => 'exists:asset_items,id',
        ]);

        $count = 0;
        foreach ($request->asset_item_ids as $assetItemId) {
            $depreciation = AssetDepreciation::where('asset_item_id', $assetItemId)->first();
            if ($depreciation) {
                $depreciation->recalculateDepreciation();
                $count++;
            }
        }

        return redirect()->route('asset.depreciation.index')
            ->with('success', "Depreciation recalculated for {$count} asset items.");
    }

    private function calculateInitialDepreciationRate($originalValue, $salvageValue, $usefulLifeYears, $method)
    {
        switch ($method) {
            case AssetDepreciation::METHOD_STRAIGHT_LINE:
                return ($originalValue - $salvageValue) / $usefulLifeYears;
            
            case AssetDepreciation::METHOD_DECLINING_BALANCE:
                // 20% declining balance rate
                return ($originalValue - $salvageValue) * 0.2;
            
            case AssetDepreciation::METHOD_SUM_OF_YEARS:
                $sumOfYears = ($usefulLifeYears * ($usefulLifeYears + 1)) / 2;
                return ($originalValue - $salvageValue) / $sumOfYears;
            
            default:
                return ($originalValue - $salvageValue) / $usefulLifeYears;
        }
    }
}
