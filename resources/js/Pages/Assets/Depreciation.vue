<template>
    <AuthenticatedLayout title="Asset Depreciation" description="Manage and track asset depreciation calculations">
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white shadow-xl rounded-2xl">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-700 p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="p-3 bg-white/20 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-10 h-10 text-white">
                                    <path d="M12 7.5v2.25m0-2.25v-1.5m0 2.25l-2.25 2.25m2.25-2.25l2.25 2.25M3.375 7.5h2.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125H3.375c-.621 0-1.125-.504-1.125-1.125V8.625c0-.621.504-1.125 1.125-1.125zm0 6.75h2.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125H3.375c-.621 0-1.125-.504-1.125-1.125v-2.25c0-.621.504-1.125 1.125-1.125zM17.25 7.5h-2.25c-.621 0-1.125.504-1.125 1.125v2.25c0 .621.504 1.125 1.125 1.125h2.25c.621 0 1.125-.504 1.125-1.125V8.625c0-.621-.504-1.125-1.125-1.125zm0 6.75h-2.25c-.621 0-1.125.504-1.125 1.125v2.25c0 .621.504 1.125 1.125 1.125h2.25c.621 0 1.125-.504 1.125-1.125v-2.25c0-.621-.504-1.125-1.125-1.125z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white">Asset Depreciation</h1>
                                <p class="text-purple-100 text-sm mt-1">Manage and track asset depreciation calculations</p>
                            </div>
                        </div>
                        <div class="mt-6 sm:mt-0">
                            <button @click="showCreateModal = true" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-purple-700 bg-white hover:bg-purple-50 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 mr-2">
                                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                                </svg>
                                Add Depreciation
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white shadow-xl rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Value</p>
                            <p class="text-2xl font-semibold text-gray-900">${{ stats.totalValue || '0.00' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-xl rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Depreciated Value</p>
                            <p class="text-2xl font-semibold text-gray-900">${{ stats.depreciatedValue || '0.00' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-xl rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Current Value</p>
                            <p class="text-2xl font-semibold text-gray-900">${{ stats.currentValue || '0.00' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-xl rounded-2xl p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Fully Depreciated</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ stats.fullyDepreciated || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white shadow-xl rounded-2xl p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input v-model="filters.search" type="text" placeholder="Search assets..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" @input="debouncedSearch" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Method</label>
                        <select v-model="filters.depreciation_method" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" @change="applyFilters">
                            <option value="">All Methods</option>
                            <option value="straight_line">Straight Line</option>
                            <option value="declining_balance">Declining Balance</option>
                            <option value="sum_of_years">Sum of Years</option>
                            <option value="units_of_production">Units of Production</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Asset Item</label>
                        <select v-model="filters.asset_item_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" @change="applyFilters">
                            <option value="">All Assets</option>
                            <option v-for="item in assetItems" :key="item.id" :value="item.id">
                                {{ item.asset_tag }} - {{ item.asset_name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Value Range</label>
                        <select v-model="filters.value_range" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" @change="applyFilters">
                            <option value="">All Values</option>
                            <option value="0-1000">$0 - $1,000</option>
                            <option value="1000-10000">$1,000 - $10,000</option>
                            <option value="10000-100000">$10,000 - $100,000</option>
                            <option value="100000+">$100,000+</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex space-x-2">
                    <button @click="clearFilters" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Clear Filters
                    </button>
                    <button @click="bulkRecalculate" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        Bulk Recalculate
                    </button>
                </div>
            </div>

            <!-- Depreciation Table -->
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Depreciation Records</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Original Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Depreciation</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Calculated</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="depreciation in depreciationRecords.data" :key="depreciation.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ depreciation.assetItem?.asset_tag }}</div>
                                        <div class="text-sm text-gray-500">{{ depreciation.assetItem?.asset_name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" :class="getMethodBadgeClass(depreciation.depreciation_method)">
                                        {{ formatMethod(depreciation.depreciation_method) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ depreciation.original_value || '0.00' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ depreciation.current_value || '0.00' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ depreciation.accumulated_depreciation || '0.00' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatDate(depreciation.last_calculated_date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button @click="viewDepreciation(depreciation)" class="text-blue-600 hover:text-blue-900">View</button>
                                        <button @click="editDepreciation(depreciation)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                        <button @click="recalculateDepreciation(depreciation.id)" class="text-green-600 hover:text-green-900">Recalculate</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="depreciationRecords.links && depreciationRecords.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                    <Pagination :links="depreciationRecords.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const props = defineProps({
    depreciationRecords: Object,
    filters: Object,
    assetItems: Array,
    stats: Object,
})

const filters = ref({
    search: props.filters?.search || '',
    depreciation_method: props.filters?.depreciation_method || '',
    asset_item_id: props.filters?.asset_item_id || '',
    value_range: props.filters?.value_range || '',
})

const debouncedSearch = debounce(() => {
    applyFilters()
}, 300)

const applyFilters = () => {
            router.get('/api/depreciation', filters.value, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    filters.value = {
        search: '',
        depreciation_method: '',
        asset_item_id: '',
        value_range: '',
    }
    applyFilters()
}

const recalculateDepreciation = (id) => {
    if (confirm('Recalculate depreciation for this asset?')) {
        router.put(`/api/depreciation/${id}/recalculate`)
    }
}

const bulkRecalculate = () => {
    if (confirm('Recalculate depreciation for all assets? This may take a moment.')) {
        router.post('/api/depreciation/bulk-recalculate')
    }
}

const getMethodBadgeClass = (method) => {
    const classes = {
        straight_line: 'bg-blue-100 text-blue-800',
        declining_balance: 'bg-green-100 text-green-800',
        sum_of_years: 'bg-purple-100 text-purple-800',
        units_of_production: 'bg-yellow-100 text-yellow-800',
    }
    return classes[method] || 'bg-gray-100 text-gray-800'
}

const formatMethod = (method) => {
    const methods = {
        straight_line: 'Straight Line',
        declining_balance: 'Declining Balance',
        sum_of_years: 'Sum of Years',
        units_of_production: 'Units of Production',
    }
    return methods[method] || method
}

const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}
</script>
