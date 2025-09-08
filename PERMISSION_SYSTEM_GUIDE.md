# Custom Permission System Guide

## Overview
This system provides a custom permission management solution without external packages, using Laravel's built-in Gates and a custom permission model.

## Database Structure

### Tables
- **`permissions`** - Stores all available permissions
- **`permission_user`** - Many-to-many relationship between users and permissions
- **`regions`** - Somali administrative regions
- **`districts`** - Somali districts linked to regions

### Permission Fields
- `name` - Unique permission identifier (e.g., 'facility-view')
- `guard_name` - Authentication guard (default: 'web')
- `display_name` - Human-readable name (e.g., 'View Facilities')
- `description` - Detailed description of the permission
- `module` - Grouping by module (e.g., 'Facility Management')

## Backend Usage (Gates)

### In Controllers
```php
use Illuminate\Support\Facades\Gate;

public function index(Request $request)
{
    // Check single permission
    if (!Gate::allows('facility-view')) {
        abort(403, 'Access denied.');
    }
    
    // Your controller logic here
}
```

### In Blade Templates
```blade
@can('facility-create')
    <a href="{{ route('facilities.create') }}" class="btn">Create Facility</a>
@endcan

@cannot('facility-edit')
    <span class="text-muted">View Only</span>
@endcannot

@canany(['facility-view', 'facility-edit'])
    <div>User can view or edit facilities</div>
@endcanany
```

### In Routes (Middleware)
```php
Route::middleware(['auth', 'permission:facility-view'])->group(function () {
    Route::get('/facilities', [FacilityController::class, 'index']);
});
```

## Frontend Usage (Vue.js)

### Import the Composable
```javascript
import { usePermissions } from '@/Composables/usePermissions'

const { hasPermissionTo, hasAnyPermission, isViewOnly } = usePermissions()
```

### Conditional Rendering
```vue
<template>
    <!-- Show button only if user has permission -->
    <button v-if="hasPermissionTo('facility-create')" @click="createFacility">
        Create Facility
    </button>
    
    <!-- Show different content for view-only users -->
    <div v-if="isViewOnly" class="text-muted">
        You have view-only access
    </div>
    
    <!-- Check multiple permissions -->
    <div v-if="hasAnyPermission(['facility-edit', 'facility-delete'])">
        User can edit or delete facilities
    </div>
</template>
```

### Available Methods
- `hasPermissionTo(permission)` - Check single permission
- `hasAnyPermission(permissions)` - Check if user has any of the permissions
- `hasAllPermissions(permissions)` - Check if user has all permissions
- `canAccessModule(module)` - Check module access
- `isViewOnly` - Check if user is view-only
- `canPerformActions` - Check if user can perform actions
- `isSystemManager` - Check if user is system manager
- `isAdmin` - Check if user is admin

## Available Permissions

### User Management
- `user-view` - View users
- `user-create` - Create users
- `user-edit` - Edit users
- `user-delete` - Delete users

### Facility Management
- `facility-view` - View facilities
- `facility-create` - Create facilities
- `facility-edit` - Edit facilities
- `facility-delete` - Delete facilities
- `facility-import` - Import facilities from Excel

### Product Management
- `product-view` - View products
- `product-create` - Create products
- `product-edit` - Edit products
- `product-delete` - Delete products
- `product-import` - Import products from Excel

### Inventory Management
- `inventory-view` - View inventory
- `inventory-adjust` - Adjust inventory
- `inventory-transfer` - Transfer inventory

### Warehouse Management
- `warehouse-view` - View warehouses
- `warehouse-manage` - Manage warehouses

### Reports
- `reports-view` - View reports
- `reports-export` - Export reports

### System Administration
- `system-settings` - Modify system settings
- `permission-manage` - Manage user permissions
- `view-only-access` - View-only access to all modules
- `manager-system` - Full system access

## Special Access Levels

### Admin Users
- Users with email `admin@warehouse.com` or `admin@admin.com`
- Username `admin` or `administrator`
- Have access to everything automatically

### System Managers
- Users with `manager-system` permission
- Have full access to all system features

### View-Only Users
- Users with `view-only-access` permission
- Can view all modules but cannot perform any actions
- Perfect for auditors, supervisors, or read-only access needs

## Adding New Permissions

### 1. Add to PermissionSeeder
```php
[
    'name' => 'new-permission',
    'display_name' => 'New Permission',
    'description' => 'Description of what this permission allows',
    'module' => 'Module Name',
    'guard_name' => 'web',
]
```

### 2. Run Seeder
```bash
php artisan db:seed --class=PermissionSeeder
```

### 3. Add Gate Definition (Optional)
```php
// In AuthServiceProvider
Gate::define('new-permission', function (User $user) {
    return $user->hasPermission('new-permission');
});
```

### 4. Use in Frontend
```vue
<button v-if="hasPermissionTo('new-permission')">
    Action Button
</button>
```

## Testing Permissions

### Check User Permissions
```php
// In tinker or controller
$user = User::find(1);
$user->hasPermission('facility-view'); // true/false
$user->hasAnyPermission(['facility-view', 'facility-edit']); // true/false
$user->getPermissionNames(); // Array of permission names
```

### Check Gates
```php
Gate::allows('facility-view'); // true/false
Gate::forUser($user)->allows('facility-view'); // true/false
```

## Security Features

- **Permission-based access control** - Users only see what they're allowed to see
- **View-only mode** - Special access level for read-only users
- **Admin bypass** - Admin users automatically have all permissions
- **Module grouping** - Permissions organized by functional areas
- **Audit trail** - All permission assignments tracked with timestamps

## Best Practices

1. **Always check permissions** in both backend (Gates) and frontend (composables)
2. **Use descriptive permission names** that clearly indicate the action
3. **Group permissions by module** for better organization
4. **Test permission logic** thoroughly before deployment
5. **Document new permissions** when adding them
6. **Use middleware** for route-level protection
7. **Implement graceful degradation** for users without permissions

## Troubleshooting

### Common Issues
- **Permission not working**: Check if permission exists in database
- **Gate not defined**: Ensure permission is seeded and Gate is defined
- **Frontend not updating**: Verify user permissions are loaded in page props
- **Cache issues**: Clear application cache if permissions aren't updating

### Debug Commands
```bash
# Check permissions table
php artisan tinker --execute="DB::table('permissions')->get()"

# Check user permissions
php artisan tinker --execute="App\Models\User::find(1)->permissions"

# Clear caches
php artisan cache:clear
php artisan config:clear
```
