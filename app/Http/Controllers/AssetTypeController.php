<?php

namespace App\Http\Controllers;

use App\Models\AssetType;
use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetTypeController extends Controller
{
    public function index()
    {
        $types = AssetType::with('category')->paginate(20);
        $categories = AssetCategory::all();
        return Inertia::render('Assets/Types/Index', [
            'types' => $types,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:asset_types,name',
            'asset_category_id' => 'nullable|exists:asset_categories,id',
        ]);
        $type = AssetType::create($data);
        return response()->json($type, 201);
    }

    public function update(Request $request, AssetType $assetType)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:asset_types,name,' . $assetType->id,
            'asset_category_id' => 'nullable|exists:asset_categories,id',
        ]);
        $assetType->update($data);
        return back()->with('success', 'Type updated');
    }

    public function destroy(AssetType $assetType)
    {
        $assetType->delete();
        return back()->with('success', 'Type deleted');
    }
}

