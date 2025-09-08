<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Throwable;

class AssetCategoryController extends Controller
{
    public function index()
    {
        $categories = AssetCategory::all();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255'
            ]);
    
            $category = AssetCategory::create($request->all());
            $category['isAddNew'] = false;
            return response()->json($category, 200);
        } catch (Throwable $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
