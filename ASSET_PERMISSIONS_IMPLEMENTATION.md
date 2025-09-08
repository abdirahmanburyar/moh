# Asset Permissions Implementation Guide

## Overview

This document describes the comprehensive asset permission system implemented using Laravel Gates to secure asset routes and prevent unauthorized access, even through direct URL manipulation.

## What Was Implemented

### 1. **Laravel Gates in AuthServiceProvider**

All asset-related permissions are now defined as Laravel Gates in `app/Providers/AuthServiceProvider.php`:

```php
// Asset-specific permission gates
Gate::define('asset-view', function ($user) {
    return $user->hasPermissionTo('asset-view') || 
           $user->hasPermissionTo('asset-manage') || 
           $user->hasPermissionTo('manage-system') || 
           $user->hasPermissionTo('view-system');
});

Gate::define('asset-create', function ($user) {
    return $user->hasPermissionTo('asset-create') || 
           $user->hasPermissionTo('asset-manage') || 
           $user->hasPermissionTo('manage-system');
});

Gate::define('asset-edit', function ($user) {
    return $user->hasPermissionTo('asset-edit') || 
           $user->hasPermissionTo('asset-manage') || 
           $user->hasPermissionTo('manage-system');
});

Gate::define('asset-delete', function ($user) {
    return $user->hasPermissionTo('asset-delete') || 
           $user->hasPermissionTo('asset-manage') || 
           $user->hasPermissionTo('manage-system');
});

Gate::define('asset-approve', function ($user) {
    return $user->hasPermissionTo('asset-approve') || 
           $user->hasPermissionTo('asset-manage') || 
           $user->hasPermissionTo('manage-system');
});

Gate::define('asset-review', function ($user) {
    return $user->hasPermissionTo('asset-review') || 
           $user->hasPermissionTo('asset-manage') || 
           $user->hasPermissionTo('manage-system');
});

Gate::define('asset-manage', function ($user) {
    return $user->hasPermissionTo('asset-manage') || 
           $user->hasPermissionTo('manage-system');
});

Gate::define('asset-bulk-import', function ($user) {
    return $user->hasPermissionTo('asset-bulk-import') || 
           $user->hasPermissionTo('asset-manage') || 
           $user->hasPermissionTo('manage-system');
});

Gate::define('asset-export', function ($user) {
    return $user->hasPermissionTo('asset-export') || 
           $user->hasPermissionTo('asset-manage') || 
           $user->hasPermissionTo('manage-system');
});
```

### 2. **Route Middleware Protection**

All asset routes are now protected with appropriate middleware using Laravel's built-in `can` middleware:

```php
// Asset Management Routes
Route::controller(AssetController::class)
    ->prefix('assets-management')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        // View routes - require asset-view permission
        Route::get('/', 'index')->name('assets.index')->middleware('can:asset-view');
        Route::get('/{asset}', 'show')->name('assets.show')->middleware('can:asset-view');
        
        // Create routes - require asset-create permission
        Route::get('/create', 'create')->name('assets.create')->middleware('can:asset-create');
        Route::post('/store', 'store')->name('assets.store')->middleware('can:asset-create');
        
        // Edit routes - require asset-edit permission
        Route::get('/{asset}/edit', 'edit')->name('assets.edit')->middleware('can:asset-edit');
        Route::put('/{asset}', 'update')->name('assets.update')->middleware('can:asset-edit');
        
        // Delete routes - require asset-delete permission
        Route::delete('/{asset}', 'destroy')->name('assets.destroy')->middleware('can:asset-delete');
        
        // Approval routes - require asset-approve permission
        Route::post('/{asset}/approve', 'approve')->name('assets.approve')->middleware('can:asset-approve');
        Route::post('/{asset}/reject', 'reject')->name('assets.reject')->middleware('can:asset-approve');
        Route::post('/{asset}/review', 'review')->name('assets.review')->middleware('can:asset-review');
        Route::post('/{asset}/restore', 'restore')->name('assets.restore')->middleware('can:asset-approve');
        
        // Transfer routes - require asset-manage permission
        Route::post('/{asset}/transfer', 'transferAsset')->name('assets.transfer')->middleware('can:asset-manage');
        
        // Document routes - require asset-edit permission
        Route::post('/{asset}/documents', [AssetDocumentController::class, 'store'])->middleware('can:asset-edit');
        Route::delete('/documents/{document}', [AssetDocumentController::class, 'destroy'])->middleware('can:asset-edit');
        
        // Maintenance routes - require asset-edit permission
        Route::get('/{asset}/maintenance', [AssetMaintenanceController::class, 'index'])->middleware('can:asset-edit');
        Route::post('/{asset}/maintenance', [AssetMaintenanceController::class, 'store'])->middleware('can:asset-edit');
    });
```

### 3. **Exception Handling**

Custom exception handling for authorization failures in `bootstrap/app.php`:

```php
// Handle authorization exceptions (403 Forbidden)
$exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
    if ($request->expectsJson()) {
        return response()->json([
            'error' => 'Access Denied',
            'message' => $e->getMessage(),
            'required_permission' => $e->getMessage(),
        ], 403);
    }

    // For web requests, redirect to permission denied page
    return redirect()->route('permission-denied', [
        'permission' => $e->getMessage()
    ]);
});
```

### 4. **Permission Denied Page**

Custom error page for permission failures at `resources/js/Pages/Errors/PermissionDenied.vue`:

- Shows which permission is missing
- Provides helpful guidance
- Links back to assets page and dashboard

### 5. **Frontend Permission Checks**

The frontend still maintains permission checks for UI elements, but now the backend provides complete security:

```vue
<!-- Review Button -->
<button v-if="page.props.auth.can.asset_review || page.props.auth.isAdmin" 
        @click="changeAssetStatus('reviewed')"
        :disabled="isReviewing || !(page.props.auth.can.asset_review || page.props.auth.isAdmin)"
        :class="[
            !(page.props.auth.can.asset_review || page.props.auth.isAdmin)
                ? 'bg-gray-400 cursor-not-allowed'
                : 'bg-yellow-500 hover:bg-yellow-600'
        ]">
    Review
</button>
```

## Permission Hierarchy

### **Permission Levels:**

1. **`asset-view`** - Basic viewing access
   - View asset list
   - View asset details
   - View asset history
   - View approvals/workflow

2. **`asset-create`** - Create new assets
   - Access create form
   - Submit new assets

3. **`asset-edit`** - Modify existing assets
   - Edit asset details
   - Manage documents
   - Manage maintenance records

4. **`asset-delete`** - Remove assets
   - Delete assets from system

5. **`asset-review`** - Review assets
   - Mark assets as reviewed

6. **`asset-approve`** - Approve/reject assets
   - Approve assets
   - Reject assets
   - Restore rejected assets

7. **`asset-manage`** - Advanced management
   - Transfer assets
   - Manage locations/categories
   - Manage assignees

8. **`asset-bulk-import`** - Bulk operations
   - Import multiple assets

9. **`asset-export`** - Export functionality
   - Download templates
   - Export data

### **Permission Inheritance:**

- **`asset-manage`** includes all lower-level permissions
- **`manage-system`** includes all asset permissions
- **`view-system`** includes `asset-view` permission

## Security Features

### **1. Route Protection**
- All asset routes are protected with `can:permission` middleware
- Direct URL access is blocked for unauthorized users
- 403 Forbidden responses for unauthorized requests

### **2. Frontend Security**
- UI elements are hidden/disabled based on permissions
- Buttons show permission requirements when disabled
- Consistent user experience

### **3. Error Handling**
- Graceful error handling for permission failures
- Custom error pages for better UX
- JSON responses for API requests

### **4. Permission Validation**
- Gates check actual database permissions
- Fallback to higher-level permissions
- Admin override capability

## Testing

### **Test Route**
A test route is available at `/test-asset-permissions` to verify Gate functionality:

```bash
GET /test-asset-permissions
```

Returns current user's asset permissions status.

### **Manual Testing**
1. Try accessing asset routes with different user permissions
2. Verify unauthorized access redirects to permission denied page
3. Check that frontend UI reflects permission status
4. Test API endpoints with insufficient permissions

## Benefits

### **1. Complete Security**
- Frontend + Backend protection
- No bypass through direct URL access
- Consistent permission enforcement

### **2. User Experience**
- Clear feedback on permission requirements
- Disabled buttons with explanations
- Helpful error pages

### **3. Maintainability**
- Centralized permission logic
- Easy to modify permissions
- Clear permission hierarchy

### **4. Scalability**
- Easy to add new asset permissions
- Consistent pattern across modules
- Reusable permission system

## Usage Examples

### **Checking Permissions in Controllers:**
```php
public function store(Request $request)
{
    // Gate automatically checks permission via middleware
    // If user lacks 'asset-create' permission, they won't reach this method
    
    $asset = Asset::create($request->validated());
    return redirect()->route('assets.show', $asset);
}
```

### **Checking Permissions in Blade Templates:**
```php
@can('asset-edit')
    <a href="{{ route('assets.edit', $asset) }}">Edit Asset</a>
@endcan
```

### **Checking Permissions in Vue Components:**
```vue
<button v-if="page.props.auth.can.asset_edit">
    Edit Asset
</button>
```

## Troubleshooting

### **Common Issues:**

1. **Permission Denied Errors**
   - Check if user has required permissions in database
   - Verify Gate definitions in AuthServiceProvider
   - Clear config cache: `php artisan config:clear`

2. **Routes Not Working**
   - Clear route cache: `php artisan route:clear`
   - Check middleware registration
   - Verify permission names match exactly

3. **Frontend Permission Issues**
   - Check `page.props.auth.can` object
   - Verify permission names in frontend
   - Clear browser cache

### **Debug Commands:**
```bash
# Clear all caches
php artisan optimize:clear

# List all routes with middleware
php artisan route:list --middleware

# Test specific permission
php artisan tinker
>>> Gate::allows('asset-view')
```

## Conclusion

This implementation provides a robust, secure, and user-friendly asset permission system that prevents unauthorized access at both the frontend and backend levels. The combination of Laravel Gates, route middleware, and frontend checks ensures complete security while maintaining excellent user experience.
