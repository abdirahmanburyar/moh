<template>
    <AuthenticatedLayout title="Asset History" description="Track all asset-related activities and changes">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white shadow-xl rounded-2xl">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-700 p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="p-3 bg-white/20 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="w-10 h-10 text-white">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white">Asset History</h1>
                                <p class="text-purple-100 text-sm mt-1">
                                    Track all asset-related activities and changes
                                </p>
                            </div>
                        </div>
                        <div class="mt-6 sm:mt-0">
                            <button @click="showCreateModal = true"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-purple-700 bg-white hover:bg-purple-50 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="w-4 h-4 mr-2">
                                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                                </svg>
                                Add History Record
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white shadow-xl rounded-2xl p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Search by asset number..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                            @input="debouncedSearch"
                        />
                    </div>

                    <!-- Action Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Action Type</label>
                        <select
                            v-model="filters.action_type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                            @change="applyFilters"
                        >
                            <option value="">All Types</option>
                            <option v-for="(label, value) in actionTypes" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Asset -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Asset</label>
                        <select
                            v-model="filters.asset_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                            @change="applyFilters"
                        >
                            <option value="">All Assets</option>
                            <option v-for="asset in assets" :key="asset.id" :value="asset.id">
                                {{ asset.asset_number }}
                            </option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                        <input
                            v-model="filters.date_from"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                            @change="applyFilters"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                        <input
                            v-model="filters.date_to"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Clear Filters -->
                    <div class="flex items-end">
                        <button
                            @click="clearFilters"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- History Table -->
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">History Records</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Asset
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Performed By
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="record in history.data" :key="record.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ record.asset_item?.asset_tag || 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ record.action }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="getActionTypeClass(record.action_type)">
                                        {{ actionTypes[record.action_type] || record.action_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ record.performer?.name || 'System' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ formatDate(record.performed_at) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button @click="viewHistory(record)" class="text-purple-600 hover:text-purple-900">
                                            View
                                        </button>
                                        <button @click="editHistory(record)" class="text-indigo-600 hover:text-indigo-900">
                                            Edit
                                        </button>
                                        <button
                                            @click="deleteRecord(record.id)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="history.links && history.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                    <Pagination :links="history.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const props = defineProps({
    history: Object,
    filters: Object,
    assets: Array,
    actionTypes: Object,
})

const filters = ref({
    search: props.filters?.search || '',
    action_type: props.filters?.action_type || '',
    asset_id: props.filters?.asset_id || '',
    performed_by: props.filters?.performed_by || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
})

const debouncedSearch = debounce(() => {
    applyFilters()
}, 300)

const applyFilters = () => {
            router.get('/api/history', filters.value, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    filters.value = {
        search: '',
        action_type: '',
        asset_id: '',
        performed_by: '',
        date_from: '',
        date_to: '',
    }
    applyFilters()
}

const deleteRecord = (id) => {
    if (confirm('Are you sure you want to delete this history record?')) {
        router.delete(`/api/history/${id}`)
    }
}

const getActionTypeClass = (actionType) => {
    const classes = {
        'status_change': 'bg-blue-100 text-blue-800',
        'transfer': 'bg-green-100 text-green-800',
        'retirement': 'bg-red-100 text-red-800',
        'approval': 'bg-purple-100 text-purple-800',
        'maintenance': 'bg-yellow-100 text-yellow-800',
        'depreciation': 'bg-indigo-100 text-indigo-800',
    }
    return classes[actionType] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}
</script>
