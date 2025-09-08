<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $maintenance = AssetMaintenance::with(['asset', 'createdBy'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('maintenance_type', 'like', '%' . $request->search . '%')
                      ->orWhereHas('asset', function ($q) use ($request) {
                          $q->where('asset_number', 'like', '%' . $request->search . '%');
                      });
            })
            ->when($request->filled('maintenance_type'), function ($query) use ($request) {
                $query->where('maintenance_type', $request->maintenance_type);
            })
            ->when($request->filled('asset_id'), function ($query) use ($request) {
                $query->where('asset_id', $request->asset_id);
            })
            ->when($request->filled('created_by'), function ($query) use ($request) {
                $query->where('created_by', $request->created_by);
            })
            ->when($request->filled('maintenance_range'), function ($query) use ($request) {
                $query->where('maintenance_range', $request->maintenance_range);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('AssetMaintenance/Index', [
            'maintenance' => $maintenance,
            'filters' => $request->only(['search', 'maintenance_type', 'asset_id', 'created_by', 'maintenance_range']),
            'assets' => Asset::select('id', 'asset_number')->get(),
            'users' => User::select('id', 'name')->get(),
            'maintenanceRanges' => AssetMaintenance::getMaintenanceRanges(),
        ]);
    }

    public function create()
    {
        return Inertia::render('AssetMaintenance/Create', [
            'assets' => Asset::select('id', 'asset_number')->get(),
            'maintenanceRanges' => AssetMaintenance::getMaintenanceRanges(),
        ]);
    }

    public function store(Request $request, Asset $asset)
    {
        $request->validate([
            'maintenance_type' => 'required|string|max:255',
            'maintenance_range' => 'required|integer|min:0|max:60',
        ]);

        $maintenance = AssetMaintenance::create([
            'asset_id' => $asset->id,
            'maintenance_type' => $request->maintenance_type,
            'maintenance_range' => $request->maintenance_range,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record created successfully.',
            'maintenance' => $maintenance
        ]);
    }

    public function show(AssetMaintenance $maintenance)
    {
        $maintenance->load(['asset', 'createdBy']);
        
        return Inertia::render('AssetMaintenance/Show', [
            'maintenance' => $maintenance,
        ]);
    }

    public function edit(AssetMaintenance $maintenance)
    {
        $maintenance->load(['asset']);
        
        return Inertia::render('AssetMaintenance/Edit', [
            'maintenance' => $maintenance,
            'assets' => Asset::select('id', 'asset_number')->get(),
            'maintenanceRanges' => AssetMaintenance::getMaintenanceRanges(),
        ]);
    }

    public function update(Request $request, AssetMaintenance $maintenance)
    {
        $request->validate([
            'maintenance_type' => 'required|string|max:255',
            'maintenance_range' => 'required|integer|min:0|max:60',
        ]);

        $maintenance->update([
            'maintenance_type' => $request->maintenance_type,
            'maintenance_range' => $request->maintenance_range,
        ]);

        return redirect()->route('asset.maintenance.index')
            ->with('success', 'Maintenance updated successfully.');
    }

    public function destroy(AssetMaintenance $maintenance)
    {
        $maintenance->delete();

        return redirect()->route('asset.maintenance.index')
            ->with('success', 'Maintenance record deleted successfully.');
    }



    public function getAssetMaintenance($assetId)
    {
        $maintenance = AssetMaintenance::with(['createdBy'])
            ->where('asset_id', $assetId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'maintenance' => $maintenance
        ]);
    }

    public function markCompleted(Request $request, AssetMaintenance $maintenance)
    {
        $request->validate([
            'completed_date' => 'required|date',
        ]);

        $maintenance->update([
            'completed_date' => $request->completed_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance marked as completed.',
            'maintenance' => $maintenance
        ]);
    }
}
