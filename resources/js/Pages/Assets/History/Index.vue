<template>
    <AuthenticatedLayout
        title="Asset History"
        description="Complete audit trail of all asset activities"
        img="/assets/images/asset-header.png"
    >
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="p-3 bg-white/20 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8 text-white">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-white">Asset History</h1>
                                <p class="text-blue-100">Complete audit trail of all asset activities</p>
                            </div>
                        </div>
                        <div class="flex space-x-3">
                            <button
                                @click="exportHistory"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 mr-2">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                                Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Filters</h3>
                    <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Asset Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Asset</label>
                            <select v-model="filters.asset_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Assets</option>
                                <option v-for="asset in assets" :key="asset.id" :value="asset.id">
                                    {{ asset.asset_tag }} - {{ asset.serial_number }}
                                </option>
                            </select>
                        </div>

                        <!-- Action Type Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Action Type</label>
                            <select v-model="filters.action_type" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Actions</option>
                                <option value="approval">Approvals</option>
                                <option value="transfer">Transfers</option>
                                <option value="retirement">Retirements</option>
                                <option value="status_change">Status Changes</option>
                            </select>
                        </div>

                        <!-- Performer Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Performed By</label>
                            <input
                                v-model="filters.performer"
                                type="text"
                                placeholder="Search by name..."
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input
                                    v-model="filters.date_from"
                                    type="date"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                />
                                <input
                                    v-model="filters.date_to"
                                    type="date"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="md:col-span-2 lg:col-span-4 flex space-x-3">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                            >
                                Apply Filters
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Clear Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- History Timeline -->
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">Activity Timeline</h3>
                        <div class="text-sm text-gray-500">
                            Showing {{ history.data.length }} of {{ history.total }} records
                        </div>
                    </div>

                    <div v-if="history.data.length === 0" class="text-center py-12">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-16 h-16 text-gray-400 mx-auto mb-4">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        <p class="text-gray-500 text-lg">No history records found</p>
                        <p class="text-gray-400">Try adjusting your filters or check back later</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="record in history.data" :key="record.id" class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start space-x-4">
                                <!-- Action Icon -->
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                         :class="getActionIconClass(record.action_type)">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-white">
                                            <path v-if="record.action_type === 'approval'" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            <path v-else-if="record.action_type === 'transfer'" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                            <path v-else-if="record.action_type === 'retirement'" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            <path v-else d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-sm font-semibold text-gray-900">
                                            {{ formatActionTitle(record.action, record.action_type) }}
                                        </h4>
                                        <span class="text-xs text-gray-500">{{ formatDate(record.performed_at) }}</span>
                                    </div>

                                    <!-- Asset Info -->
                                    <div class="mb-2">
                                                                                 <Link
                                             :href="route('assets.show', record.asset_item.asset.id)"
                                             class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                         >
                                             {{ record.asset_item?.asset_tag || 'Unknown Asset' }}
                                         </Link>
                                         <span class="text-sm text-gray-500 ml-2">
                                             {{ record.asset_item?.serial_number || 'No Serial' }}
                                         </span>
                                    </div>

                                    <!-- Performer Info -->
                                    <p class="text-sm text-gray-600 mb-2">
                                        Performed by <span class="font-medium">{{ record.performer?.name || 'Unknown User' }}</span>
                                    </p>

                                    <!-- Notes -->
                                    <p v-if="record.notes" class="text-sm text-gray-700 bg-gray-100 p-2 rounded">
                                        {{ record.notes }}
                                    </p>

                                    <!-- Change Details -->
                                    <div v-if="record.old_value || record.new_value" class="mt-3 text-xs text-gray-500">
                                        <div v-if="record.old_value" class="mb-1">
                                            <span class="font-medium">From:</span> {{ formatValue(record.old_value) }}
                                        </div>
                                        <div v-if="record.new_value">
                                            <span class="font-medium">To:</span> {{ formatValue(record.new_value) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="history.last_page > 1" class="mt-6">
                        <Pagination :links="history.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import moment from 'moment';

const props = defineProps({
    history: Object,
    filters: Object,
    assets: Array,
});

const filters = ref({
    asset_id: props.filters?.asset_id || '',
    action_type: props.filters?.action_type || '',
    performer: props.filters?.performer || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

// Helper functions
function getActionIconClass(actionType) {
    const classes = {
        'approval': 'bg-green-500',
        'transfer': 'bg-blue-500',
        'retirement': 'bg-red-500',
        'status_change': 'bg-yellow-500'
    };
    return classes[actionType] || 'bg-gray-500';
}

function formatActionTitle(action, actionType) {
    const titles = {
        'reviewed': 'Asset Reviewed',
        'approved': 'Asset Approved',
        'rejected': 'Asset Rejected',
        'transfer_reviewed': 'Transfer Reviewed',
        'transfer_approved': 'Transfer Approved',
        'transfer_rejected': 'Transfer Rejected',
        'retirement_reviewed': 'Retirement Reviewed',
        'retirement_approved': 'Retirement Approved',
        'retirement_rejected': 'Retirement Rejected',
        'status_changed': 'Status Changed',

    };
    return titles[action] || ucfirst(action.replace('_', ' '));
}

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatDate(date) {
    return moment(date).format('MMM DD, YYYY HH:mm');
}

function formatValue(value) {
    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2);
    }
    return value;
}

// Filter functions
function applyFilters() {
    router.get(route('assets.history.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    filters.value = {
        asset_id: '',
        action_type: '',
        performer: '',
        date_from: '',
        date_to: '',
    };
    applyFilters();
}

function exportHistory() {
    // TODO: Implement export functionality
    alert('Export functionality will be implemented soon!');
}
</script> 