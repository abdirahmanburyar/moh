<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\UserRegistered;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Http\Resources\UserResource;
use App\Models\Permission;
use App\Events\GlobalPermissionChanged;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        
        logger()->info($request->all());
        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        


        // is active
        if ($request->has('status') && $request->status != 'All') {
            $query->where('is_active', $request->status);
        }
        
        $query->with(['permissions'])->latest();

        
        $users = $query->paginate($request->per_page, ['*'], 'page', $request->page)->withQueryString();

        $users->setPath(url()->current());
        

        
        return Inertia::render('User/Index', [
            'users' => UserResource::collection($users),
            'filters' => $request->only(['search', 'role', 'status', 'per_page'])
        ]);
    }

    /**
     * Store a newly created user or update an existing one.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Normalize potential string values from the UI
            $payload = $request->all();

            $validationRules = [
                'id' => 'nullable|exists:users,id',
                'name' => 'required|string|max:255',
                'username' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'unique:users,email,' . $request->id,
                ],
                'password' => $request->id ? 'nullable|string|min:8' : 'required|string|min:8',
                'title' => 'required|string|max:255',
                'is_active' => 'nullable|boolean',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,id',
            ];

            $request->validate($validationRules);

            $userData = [
                'name' => $request->name,
                'username' => $request->username,
                'title' => $request->title,
                'email' => $request->email,
                'is_active' => $request->has('is_active') ? $request->is_active : true,
            ];

            // Store the original password for email notification
            $originalPassword = null;
            if ($request->filled('password')) {
                $originalPassword = $request->password;
                $userData['password'] = Hash::make($request->password);
            }
            
            // Check if this is a new user or an update
            $isNewUser = !$request->id;
            
            // Create or update the user
            $user = User::updateOrCreate(
                ['id' => $request->id],
                $userData
            );
            
            // Handle permissions - assign permissions if provided
            $newPermissions = $request->has('permissions') && is_array($request->permissions) 
                ? $request->permissions 
                : [];
                
            // Sync permissions using the custom User-Permission pivot table
            $user->permissions()->sync($newPermissions);

            // event(new GlobalPermissionChanged($user));
                        
            // Reload the user with relationships for the email
            $user->load(['permissions']);
            
            // Send email notification for new users asynchronously
            if ($isNewUser) {
                // Queue the notification to prevent timeout issues
                $user->notifyNow(new UserRegistered($user, $originalPassword));
            }
            
            DB::commit();
            return response()->json($request->id ? 'User updated successfully' : 'User created successfully', 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }             
    }



    public function create(Request $request)
    {
        $permissions = Permission::all();
        
        return Inertia::render('User/Create', [
            'permissions' => $permissions
        ]);
    }
    
    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $user->load(['permissions']);
        $permissions = Permission::all();
        
        return Inertia::render('User/Edit', [
            'user' => $user,
            'permissions' => $permissions
        ]);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        try {
            // Prevent deleting your own account
            if ($user->id === auth()->id()) {
                $isFromSettings = request()->header('X-From-Settings') || 
                                 (request()->has('_headers') && request()->_headers && isset(request()->_headers['X-From-Settings']));
                
                if ($isFromSettings) {
                    return redirect()->back()->withErrors(['error' => 'You cannot delete your own account.']);
                }
                
                return response()->json('You cannot delete your own account.', 500);
            }
            
            $user->delete();

            $isFromSettings = request()->header('X-From-Settings') || 
                             (request()->has('_headers') && request()->_headers && isset(request()->_headers['X-From-Settings']));
            
            if ($isFromSettings) {
                return redirect()->route('settings.index', ['tab' => 'users'])->with('success', 'User deleted successfully.');
            }

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ]);
        } catch (Throwable $e) {
            $isFromSettings = request()->header('X-From-Settings') || 
                             (request()->has('_headers') && request()->_headers && isset(request()->_headers['X-From-Settings']));
            
            if ($isFromSettings) {
                return redirect()->back()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }


    
    /**
     * Toggle a user's active status.
     */
    public function toggleStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'is_active' => 'required|boolean',
        ]);
        
        try {
            $user = User::findOrFail($request->user_id);
            $user->is_active = $request->is_active;
            $user->save();
            
            $statusText = $request->is_active ? 'activated' : 'deactivated';
            
            return response()->json([
                'success' => true,
                'message' => "User {$statusText} successfully",
                'user' => $user
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    
    /**
     * Toggle status for multiple users.
     */
    public function bulkToggleStatus(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'is_active' => 'required|boolean',
        ]);
        
        try {
            DB::beginTransaction();
            
            $count = User::whereIn('id', $request->user_ids)
                ->update(['is_active' => $request->is_active]);
            
            DB::commit();
            
            $statusText = $request->is_active ? 'activated' : 'deactivated';
            
            return response()->json([
                'success' => true,
                'message' => "{$count} users {$statusText} successfully"
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
