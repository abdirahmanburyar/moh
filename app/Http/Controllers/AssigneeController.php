<?php

namespace App\Http\Controllers;

use App\Models\Assignee;
use Illuminate\Http\Request;

class AssigneeController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:100',
        ]);

        $assignee = Assignee::create($data);
        return response()->json($assignee, 201);
    }
}

