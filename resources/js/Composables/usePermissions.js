import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function usePermissions() {
    const { props } = usePage()
    
    // Get user permissions from the page props
    const userPermissions = computed(() => {
        return props.auth?.user?.permissions || []
    })
    
    // Get permission names as array
    const permissionNames = computed(() => {
        return userPermissions.value.map(p => p.name)
    })
    
    // Check if user has a specific permission
    const hasPermissionTo = (permission) => {
        if (!permission) return false
        return permissionNames.value.includes(permission)
    }
    
    // Check if user has any of the given permissions
    const hasAnyPermission = (permissions) => {
        if (!Array.isArray(permissions)) return false
        return permissions.some(permission => hasPermissionTo(permission))
    }
    
    // Check if user has all of the given permissions
    const hasAllPermissions = (permissions) => {
        if (!Array.isArray(permissions)) return false
        return permissions.every(permission => hasPermissionTo(permission))
    }
    
    // Check if user is view-only
    const isViewOnly = computed(() => {
        return hasPermissionTo('view-only-access')
    })
    
    // Check if user is admin
    const isAdmin = computed(() => {
        return props.auth?.user?.isAdmin?.() || false
    })
    
    // Get permissions grouped by module
    const permissionsByModule = computed(() => {
        const grouped = {}
        userPermissions.value.forEach(permission => {
            if (!grouped[permission.module]) {
                grouped[permission.module] = []
            }
            grouped[permission.module].push(permission)
        })
        return grouped
    })
    
    // Check if user has facility management permissions
    const canManageFacilities = computed(() => {
        return hasAnyPermission(['facility-view', 'facility-create', 'facility-edit', 'facility-delete', 'facility-import'])
    })
    
    // Check if user has product management permissions
    const canManageProducts = computed(() => {
        return hasAnyPermission(['product-view', 'product-create', 'product-edit', 'product-delete', 'product-import'])
    })
    
    // Check if user has inventory management permissions
    const canManageInventory = computed(() => {
        return hasAnyPermission(['inventory-view', 'inventory-adjust', 'inventory-transfer'])
    })
    
    // Check if user has user management permissions
    const canManageUsers = computed(() => {
        return hasAnyPermission(['user-view', 'user-create', 'user-edit', 'user-delete'])
    })
    
    // Check if user has warehouse management permissions
    const canManageWarehouses = computed(() => {
        return hasAnyPermission(['warehouse-view', 'warehouse-manage'])
    })
    
    // Check if user has reports access
    const canAccessReports = computed(() => {
        return hasAnyPermission(['reports-view', 'reports-export'])
    })
    
    // Check if user has system administration permissions
    const canManageSystem = computed(() => {
        return hasAnyPermission(['system-settings', 'permission-manage'])
    })

    // Check if user has view-only system access
    const hasViewSystemAccess = computed(() => {
        return hasPermissionTo('view-system');
    });

    // Check if user can perform actions (not just view)
    const canPerformActions = computed(() => {
        return !hasViewSystemAccess.value && !isViewOnly.value;
    });

    // Check if user is a system manager (full authority)
    const isSystemManager = computed(() => {
        return canManageSystem.value || isAdmin.value;
    });

    // Check if user can access a specific module
    const canAccessModule = (moduleName) => {
        // System managers can access everything
        if (canManageSystem.value) return true;
        
        // View system users can see everything but not perform actions
        if (hasViewSystemAccess.value) return true;
        
        // Check specific module permissions
        const modulePermissions = {
            'facilities': ['facility-view', 'facility-create', 'facility-edit', 'facility-delete', 'facility-import'],
            'products': ['product-view', 'product-create', 'product-edit', 'product-delete', 'product-import'],
            'inventory': ['inventory-view', 'inventory-adjust', 'inventory-transfer'],
            'orders': ['order-view', 'order-create', 'order-edit', 'order-delete', 'order-approve'],
            'transfers': ['transfer-view', 'transfer-create', 'transfer-edit', 'transfer-delete', 'transfer-approve'],
            'assets': ['asset-view', 'asset-create', 'asset-edit', 'asset-delete'],
            'users': ['user-view', 'user-create', 'user-edit', 'user-delete'],
            'reports': ['report-view', 'report-export'],
            'settings': ['system-settings', 'permission-manage']
        };
        
        if (modulePermissions[moduleName]) {
            return modulePermissions[moduleName].some(permission => hasPermissionTo(permission));
        }
        
        return false;
    };
    
    return {
        // Basic permission checks
        hasPermissionTo,
        hasAnyPermission,
        hasAllPermissions,
        canAccessModule,
        
        // Computed properties
        userPermissions,
        permissionNames,
        permissionsByModule,
        isViewOnly,
        canPerformActions,
        isSystemManager,
        isAdmin,
        canManageSystem,
        hasViewSystemAccess,
        
        // Module-specific checks
        canManageFacilities,
        canManageProducts,
        canManageInventory,
        canManageUsers,
        canManageWarehouses,
        canAccessReports,
        canManageSystem,
        // Module-specific computed properties
        canManageFacilities: computed(() => canAccessModule('facilities')),
        canManageProducts: computed(() => canAccessModule('products')),
        canManageInventory: computed(() => canAccessModule('inventory')),
        canManageOrders: computed(() => canAccessModule('orders')),
        canManageTransfers: computed(() => canAccessModule('transfers')),
        canManageAssets: computed(() => canAccessModule('assets')),
        canManageUsers: computed(() => canAccessModule('users')),
        canViewReports: computed(() => canAccessModule('reports')),
        canAccessSettings: computed(() => canAccessModule('settings')),
    }
}
