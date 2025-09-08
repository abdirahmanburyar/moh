<?php

namespace App\Http\Controllers;

use App\Models\AssetLocation;
use App\Models\SubLocation;
use Illuminate\Http\Request;

class AssetLocationController extends Controller
{
    public function index(Request $request)
    {
        $locations = AssetLocation::query()
            ->when($request->search, function($query) use($request){
                $query->where('name', 'like', "%{$request->search}%");
            })
            ->get();

        return inertia("Assets/AssetLocation/Index", [
            "locations" => $locations,
            'filters' => $request->only('search')
        ]);
    }

    public function create()
    {
        return inertia("Assets/AssetLocation/Create");
    }

    public function edit($id)
    {
        $location = AssetLocation::find($id);
        return inertia("Assets/AssetLocation/Edit", [
            'location' => $location
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'id' => 'nullable',
                'name' => 'required|string|max:255'
            ]);
    
            $location = AssetLocation::updateOrCreate(['id' => $request->id], [
                'name' => $request->name
            ]);
            
            $location['isAddNew'] = false;
            logger()->info($location);
            return response()->json($location, 200);
        } catch (Throwable $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $location = AssetLocation::findOrFail($id);
            
            // Check if location has sub-locations
            if ($location->subLocations()->count() > 0) {
                return response()->json([
                    'message' => 'Cannot delete location with existing sub-locations'
                ], 422);
            }
            
            $location->delete();
            return response()->json(['message' => 'Location deleted successfully']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getSubLocations($locationId)
    {
        $subLocations = SubLocation::where('asset_location_id', $locationId)->get();
        return response()->json($subLocations);
    }

    public function storeSubLocation(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'asset_location_id' => 'required|exists:asset_locations,id'
        ]);

        $subLocation = SubLocation::create($request->all());
        return response()->json($subLocation, 201);
    }
}
