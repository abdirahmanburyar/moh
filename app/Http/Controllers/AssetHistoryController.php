<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\AssetItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetHistoryController extends Controller
{
    public function index(Request $request)
    {
        $history = AssetHistory::with(['assetItem', 'performer', 'assignee'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->whereHas('assetItem', function ($q) use ($request) {
                    $q->where('asset_tag', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->filled('action_type'), function ($query) use ($request) {
                $query->where('action_type', $request->action_type);
            })
            ->when($request->filled('asset_id'), function ($query) use ($request) {
                $query->where('asset_item_id', $request->asset_id);
            })
            ->when($request->filled('performed_by'), function ($query) use ($request) {
                $query->where('performed_by', $request->performed_by);
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->where('performed_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->where('performed_at', '<=', $request->date_to);
            })
            ->orderBy('performed_at', 'desc')
            ->paginate(15);

        return Inertia::render('AssetHistory/Index', [
            'history' => $history,
            'filters' => $request->only(['search', 'action_type', 'asset_id', 'performed_by', 'date_from', 'date_to']),
            'assets' => Asset::select('id', 'asset_number')->get(),
            'actionTypes' => [
                'status_change' => 'Status Change',
                'transfer' => 'Transfer',
                'retirement' => 'Retirement',
                'approval' => 'Approval',
                'maintenance' => 'Maintenance',
                'depreciation' => 'Depreciation',
            ],
        ]);
    }

    public function show(AssetHistory $history)
    {
        $history->load(['assetItem', 'performer', 'assignee', 'approval']);
        
        return Inertia::render('AssetHistory/Show', [
            'history' => $history,
        ]);
    }

    public function assetHistory(Asset $asset)
    {
        // Get asset item IDs for this asset
        $assetItemIds = $asset->assetItems()->pluck('id');
        
        $history = AssetHistory::with(['assetItem', 'performer', 'assignee', 'approval'])
            ->whereIn('asset_item_id', $assetItemIds)
            ->orderBy('performed_at', 'desc')
            ->paginate(15);

        return Inertia::render('AssetHistory/AssetHistory', [
            'asset' => $asset,
            'history' => $history,
        ]);
    }

    public function assetItemHistory(AssetItem $assetItem)
    {
        $history = AssetHistory::with(['assetItem', 'performer', 'assignee', 'approval'])
            ->where('asset_item_id', $assetItem->id)
            ->orderBy('performed_at', 'desc')
            ->paginate(15);

        return Inertia::render('AssetHistory/AssetItemHistory', [
            'assetItem' => $assetItem,
            'history' => $history,
        ]);
    }

    public function create()
    {
        return Inertia::render('AssetHistory/Create', [
            'assets' => Asset::select('id', 'asset_number')->get(),
            'actionTypes' => [
                'status_change' => 'Status Change',
                'transfer' => 'Transfer',
                'retirement' => 'Retirement',
                'approval' => 'Approval',
                'maintenance' => 'Maintenance',
                'depreciation' => 'Depreciation',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'action' => 'required|string|max:255',
            'action_type' => 'required|string|in:status_change,transfer,retirement,approval,maintenance,depreciation',
            'old_value' => 'nullable|array',
            'new_value' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
            'assignee_id' => 'nullable|exists:assignees,id',
        ]);

        $history = AssetHistory::create([
            'asset_item_id' => $request->asset_id,
            'action' => $request->action,
            'action_type' => $request->action_type,
            'old_value' => $request->old_value,
            'new_value' => $request->new_value,
            'notes' => $request->notes,
            'performed_by' => auth()->id(),
            'performed_at' => now(),
            'assignee_id' => $request->assignee_id,
        ]);

        return redirect()->route('asset.history.index')
            ->with('success', 'History record created successfully.');
    }

    public function edit(AssetHistory $history)
    {
        $history->load(['assetItem']);
        
        return Inertia::render('AssetHistory/Edit', [
            'history' => $history,
            'assets' => Asset::select('id', 'asset_number')->get(),
            'actionTypes' => [
                'status_change' => 'Status Change',
                'transfer' => 'Transfer',
                'retirement' => 'Retirement',
                'approval' => 'Approval',
                'maintenance' => 'Maintenance',
                'depreciation' => 'Depreciation',
            ],
        ]);
    }

    public function update(Request $request, AssetHistory $history)
    {
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'action' => 'required|string|max:255',
            'action_type' => 'required|string|in:status_change,transfer,retirement,approval,maintenance,depreciation',
            'old_value' => 'nullable|array',
            'new_value' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
            'assignee_id' => 'nullable|exists:assignees,id',
        ]);

        $history->update([
            'asset_item_id' => $request->asset_id,
            'action' => $request->action,
            'action_type' => $request->action_type,
            'old_value' => $request->old_value,
            'new_value' => $request->new_value,
            'notes' => $request->notes,
            'assignee_id' => $request->assignee_id,
        ]);

        return redirect()->route('asset.history.index')
            ->with('success', 'History record updated successfully.');
    }

    public function destroy(AssetHistory $history)
    {
        $history->delete();

        return redirect()->route('asset.history.index')
            ->with('success', 'History record deleted successfully.');
    }
}
