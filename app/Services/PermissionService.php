<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Permission;
use App\Events\GlobalPermissionChanged;

class PermissionService
{
    /**
     * Give a permission to a user and dispatch an event.
     *
     * @param User $user
     * @param string|Permission $permission
     * @return void
     */
    public function givePermissionTo(User $user, $permission)
    {
        $permissionName = $permission instanceof Permission ? $permission->name : $permission;
        
        // Only proceed if the user doesn't already have this permission
        if (!$user->hasPermissionTo($permissionName)) {
            $user->givePermissionTo($permissionName);
            
            // Update the permission_updated_at timestamp
            $user->permission_updated_at = now();
            $user->save();
            
            // Dispatch event using the GlobalPermissionChanged event
            event(new GlobalPermissionChanged($user));
        }
    }
    
    /**
     * Remove a permission from a user and dispatch an event.
     *
     * @param User $user
     * @param string|Permission $permission
     * @return void
     */
    public function revokePermissionTo(User $user, $permission)
    {
        $permissionName = $permission instanceof Permission ? $permission->name : $permission;
        
        // Only proceed if the user has this permission
        if ($user->hasPermissionTo($permissionName)) {
            $user->revokePermissionTo($permissionName);
            
            // Update the permission_updated_at timestamp
            $user->permission_updated_at = now();
            $user->save();
            
            // Dispatch event using the GlobalPermissionChanged event
            event(new GlobalPermissionChanged($user));
        }
    }
    
    /**
     * Sync permissions for a user and dispatch events for changes.
     *
     * @param User $user
     * @param array $permissions
     * @return void
     */
    public function syncPermissions(User $user, array $permissions)
    {
        // Get current permissions
        $currentPermissions = $user->getDirectPermissions()->pluck('name')->toArray();
        
        // Identify permissions to add and remove
        $permissionsToAdd = array_diff($permissions, $currentPermissions);
        $permissionsToRemove = array_diff($currentPermissions, $permissions);
        
        // If there are changes, sync and dispatch events
        if (!empty($permissionsToAdd) || !empty($permissionsToRemove)) {
            // Sync the permissions
            $user->syncPermissions($permissions);
            
            // Log permission changes
            if (!empty($permissionsToAdd)) {
                \Illuminate\Support\Facades\Log::info('Permissions added to user', [
                    'user_id' => $user->id,
                    'permissions' => $permissionsToAdd,
                    'changed_by' => Auth::user() ? Auth::user()->name : 'System'
                ]);
            }
            
            if (!empty($permissionsToRemove)) {
                \Illuminate\Support\Facades\Log::info('Permissions removed from user', [
                    'user_id' => $user->id,
                    'permissions' => $permissionsToRemove,
                    'changed_by' => Auth::user() ? Auth::user()->name : 'System'
                ]);
            }
            
            // Update the permission_updated_at timestamp
            $user->permission_updated_at = now();
            $user->save();
            
            // Dispatch a single event for all permission changes
            event(new GlobalPermissionChanged($user));
        }
    }
}
