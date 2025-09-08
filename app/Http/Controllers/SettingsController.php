<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        // Check if user can access system settings, manage permissions, manage system, or view system
        if (!Gate::allows('system-settings') && !Gate::allows('permission-manage') && !Gate::allows('manage-system') && !Gate::allows('view-system')) {
            abort(403, 'Access denied. You do not have permission to access settings.');
        }

        $tab = $request->query('tab', 'General');
        
        // Get users with filtering if tab is 'users'
        $users = User::with('permissions');
        
        if ($tab === 'users') {
            // Apply search filter if provided
            if ($request->filled('search')) {
                $search = $request->input('search');
                $users->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting if provided
            if ($request->filled('sort_field')) {
                $sortField = $request->input('sort_field', 'created_at');
                $sortDirection = $request->input('sort_direction', 'desc');
                $users->orderBy($sortField, $sortDirection);
            } else {
                $users->orderBy('created_at', 'desc');
            }
        }
        
        // Extract filters but only include non-empty ones
        $filters = [];
        if ($request->filled('search')) $filters['search'] = $request->input('search');
        if ($request->filled('sort_field')) $filters['sort_field'] = $request->input('sort_field');
        if ($request->filled('sort_direction')) $filters['sort_direction'] = $request->input('sort_direction');
        
        return Inertia::render('Settings/Index', [
            'users' => UserResource::collection($users->paginate(10)->withQueryString()),
            'permissions' => Permission::all(),
            'activeTab' => $tab,
            'filters' => $request->only('search', 'sort_field', 'sort_direction', 'tab'),
        ]);
    }
}
