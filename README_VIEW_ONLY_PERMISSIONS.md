# View-Only Permission System

## Overview

This document explains the view-only permission system implemented in the warehouse management application. The system provides a comprehensive way to grant users read-only access to all modules while preventing any modification actions.

## Permission Structure

### 1. View-Only Access Permission
- **Permission Name**: `view-only-access`
- **Purpose**: This is a system-level permission that indicates a user has view-only access
- **Behavior**: When this permission is present, the system should restrict all modification actions

### 2. Module View Permissions
The following view permissions are automatically assigned to view-only users:

#### Core Modules
- `dashboard-view` - Access to dashboard
- `user-view` - View user information
- `order-view` - View orders
- `product-view` - View products
- `inventory-view` - View inventory
- `transfer-view` - View transfers
- `asset-view` - View assets
- `purchase-order-view` - View purchase orders
- `facility-view` - View facilities
- `warehouse-view` - View warehouses
- `supplier-view` - View suppliers

#### Reporting & Settings
- `report-view` - View reports
- `setting-view` - View settings

## Implementation Details

### Permission Seeder
The `PermissionSeeder` has been updated to include:
- Better organization with clear section headers
- The new `view-only-access` permission
- Comprehensive comments for each permission group

### View-Only User Seeder
A new `ViewOnlyUserSeeder` creates a dedicated view-only user with:
- **Email**: viewonly@warehouse.com
- **Password**: password
- **Username**: viewonly
- **Permissions**: Only view-related permissions (no create, edit, delete, approve, etc.)

### Database Seeder
The main `DatabaseSeeder` now includes:
1. `PermissionSeeder` - Creates all permissions
2. `UserSeeder` - Creates Super Admin with full access
3. `ViewOnlyUserSeeder` - Creates View-Only user with restricted access

## Usage

### Running the Seeders
```bash
# Run all seeders
php artisan db:seed

# Run only specific seeders
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=ViewOnlyUserSeeder
```

### View-Only User Login
- **Email**: viewonly@warehouse.com
- **Password**: password

## Security Considerations

### What View-Only Users CAN Do
- View all modules and their data
- Access dashboard
- View reports
- View settings
- Navigate through the application

### What View-Only Users CANNOT Do
- Create new records
- Edit existing records
- Delete records
- Approve/reject workflows
- Import/export data
- Manage user permissions
- Modify system settings

## Frontend Implementation

To properly implement this permission system in the frontend, you should:

### 1. Check for View-Only Permission
```javascript
// Check if user has view-only access
if (user.hasPermission('view-only-access')) {
    // Hide all action buttons (create, edit, delete, approve, etc.)
    // Show only view-related functionality
}
```

### 2. Hide Action Buttons
When `view-only-access` permission is present, hide:
- Create buttons
- Edit buttons
- Delete buttons
- Approve/Reject buttons
- Import/Export buttons
- Toggle buttons
- Permission management buttons

### 3. Disable Forms
- Make all forms read-only
- Disable form submissions
- Show data in display mode only

## Permission Hierarchy

```
Full Access (Super Admin)
├── manager-system
├── All module permissions (create, edit, delete, approve, etc.)
└── All view permissions

View-Only Access
├── view-only-access
└── All view permissions only
    ├── dashboard-view
    ├── user-view
    ├── order-view
    ├── product-view
    ├── inventory-view
    ├── transfer-view
    ├── asset-view
    ├── purchase-order-view
    ├── facility-view
    ├── warehouse-view
    ├── supplier-view
    ├── report-view
    └── setting-view
```

## Maintenance

### Adding New Modules
When adding new modules, ensure to:
1. Add the corresponding view permission to the `PermissionSeeder`
2. Update the `ViewOnlyUserSeeder` to include the new view permission
3. Update this documentation

### Modifying Permissions
- The `view-only-access` permission should always be treated as a system-level restriction
- Any new view permissions should be automatically included for view-only users
- Never grant modification permissions to users with `view-only-access`

## Testing

### Verify View-Only Access
1. Login as viewonly@warehouse.com
2. Navigate through all modules
3. Verify that no action buttons are visible
4. Verify that all data is displayed in read-only format
5. Verify that no forms can be submitted

### Verify Full Access
1. Login as superadmin
2. Verify that all action buttons are visible
3. Verify that all functionality is available

## Troubleshooting

### Common Issues
1. **View-Only user can still see action buttons**: Check if the frontend is properly checking for `view-only-access` permission
2. **View-Only user can still submit forms**: Ensure forms are disabled when the permission is present
3. **Missing view permissions**: Run the `ViewOnlyUserSeeder` again to ensure all view permissions are assigned

### Reset Permissions
If permissions get corrupted:
```bash
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=ViewOnlyUserSeeder
```
