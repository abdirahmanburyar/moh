<template>
    <AuthenticatedLayout :title="'Settings'" description="Manage System Settings" img="/assets/images/settings.png">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Settings</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- User Management -->
            <div v-if="hasPermissionTo('permission-manage') || hasPermissionTo('system-settings') || hasPermissionTo('manage-system') || hasPermissionTo('view-system')" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4 border-b pb-2">User Management</h2>
                    
                    <div class="space-y-6">
                        <div v-if="hasPermissionTo('permission-manage') || hasPermissionTo('manage-system')">
                            <h3 class="text-lg font-medium mb-2">Users & Permissions</h3>
                            <ul class="space-y-2">
                                <li><Link :href="route('settings.users.index')" class="text-gray-600 hover:text-indigo-600">Manage Users</Link></li>
                                <li><a href="#" class="text-gray-600 hover:text-indigo-600">Permissions</a></li>
                                <li><a href="#" class="text-gray-600 hover:text-indigo-600">Audit Trials</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div v-if="hasPermissionTo('view-system') || hasPermissionTo('manage-system')" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4 border-b pb-2">System Status</h2>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-700">System Status:</span>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">Active</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-700">Database:</span>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">Connected</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-700">Version:</span>
                            <span class="text-gray-600">v1.0.0</span>
                        </div>
                    </div>
                    
                    <div v-if="hasPermissionTo('view-system') && !hasPermissionTo('manage-system')" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded">
                        <p class="text-blue-600 text-sm">
                            You have view access to system settings. 
                            Contact an administrator for management permissions.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, usePage } from "@inertiajs/vue3";
import { ref, onMounted } from 'vue';
import { usePermissions } from '@/Composables/usePermissions';

// Use permissions composable
const { hasPermissionTo } = usePermissions();

// Get page instance
const page = usePage();

onMounted(() => {
    console.log('Settings page mounted');
    console.log('Page props:', page.props);
    console.log('Auth user:', page.props.auth?.user);
    console.log('User permissions:', page.props.auth?.user?.permissions);
});
</script>
