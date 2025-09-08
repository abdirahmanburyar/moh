<template>
    <AuthenticatedLayout title="Locations">
        <div class="p-6">
            <!-- Tabs -->
            <div class="flex space-x-4 border-b border-gray-300 mb-6">
                <Link
                    v-for="tab in tabs"
                    :key="tab.name"
                    :href="route(tab.name)"
                    class="py-2 px-4 text-sm font-medium border-b-2 -mb-[2px] transition-colors"
                    :class="{
                        'border-blue-500 text-blue-600': $page.component.startsWith('Inventory/'),
                        'border-green-500 text-green-600': $page.component.startsWith('Warehouse/'),
                        'border-red-500 text-red-600': $page.component.startsWith('Location/'),
                        'border-transparent text-gray-600 hover:text-blue-500 hover:border-blue-300': !$page.component.startsWith(tab.component)
                    }"
                >
                    {{ tab.label }}
                </Link>
            </div>

            <!-- Page content -->
            <slot />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'

const tabs = [
    { name: 'inventories.index', label: 'Inventory', component: 'Inventory' },
    { name: 'inventories.warehouses.index', label: 'Warehouse', component: 'Warehouse' },
    { name: 'inventories.location.index', label: 'Locations', component: 'Location' },
]
</script>
