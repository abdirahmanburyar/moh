<template>
    <AuthenticatedLayout>
        <Head title="Dashboard" />

        <!-- Header Section -->
        <div class="bg-white shadow-xl rounded-2xl mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-white/20 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-10 h-10 text-white">
                                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Dashboard</h1>
                            <p class="text-blue-100 text-sm mt-1">
                                Overview of your system
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div v-for="item in dashboardData.summary" :key="item.label" 
                 class="bg-white rounded-xl shadow-lg p-6 border-l-4"
                 :class="{
                     'border-blue-500': item.color === 'blue',
                     'border-green-500': item.color === 'green',
                     'border-purple-500': item.color === 'purple',
                     'border-orange-500': item.color === 'orange'
                 }">
                <div class="flex items-center">
                    <div class="p-2 rounded-lg"
                         :class="{
                             'bg-blue-100': item.color === 'blue',
                             'bg-green-100': item.color === 'green',
                             'bg-purple-100': item.color === 'purple',
                             'bg-orange-100': item.color === 'orange'
                         }">
                        <svg class="w-6 h-6"
                             :class="{
                                 'text-blue-600': item.color === 'blue',
                                 'text-green-600': item.color === 'green',
                                 'text-purple-600': item.color === 'purple',
                                 'text-orange-600': item.color === 'orange'
                             }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">{{ item.fullName }}</p>
                        <p class="text-2xl font-bold text-gray-900">{{ item.value }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Tasks Section -->
            <div class="bg-white shadow-xl rounded-2xl p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Tasks</h2>
                <div v-if="dashboardData.tasks && dashboardData.tasks.length > 0" class="space-y-3">
                    <div v-for="task in dashboardData.tasks.slice(0, 5)" :key="task.id" 
                         class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                 :class="{
                                     'bg-yellow-100': task.color === 'yellow',
                                     'bg-orange-100': task.color === 'orange',
                                     'bg-blue-100': task.color === 'blue',
                                     'bg-green-100': task.color === 'green'
                                 }">
                                <svg class="w-4 h-4"
                                     :class="{
                                         'text-yellow-600': task.color === 'yellow',
                                         'text-orange-600': task.color === 'orange',
                                         'text-blue-600': task.color === 'blue',
                                         'text-green-600': task.color === 'green'
                                     }" 
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ task.title }}</p>
                            <p class="text-xs text-gray-500">{{ task.description }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                  :class="{
                                      'bg-yellow-100 text-yellow-800': task.priority === 'high',
                                      'bg-orange-100 text-orange-800': task.priority === 'medium',
                                      'bg-blue-100 text-blue-800': task.priority === 'low'
                                  }">
                                {{ task.priority }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2 text-sm">No pending tasks</p>
                </div>
            </div>

            <!-- Asset Statistics -->
            <div class="bg-white shadow-xl rounded-2xl p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Asset Status</h2>
                <div v-if="assetStatusStats && Object.keys(assetStatusStats).length > 0" class="space-y-3">
                    <div v-for="(count, status) in assetStatusStats" :key="status" 
                         class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm font-medium text-gray-700">{{ status }}</span>
                        <span class="text-lg font-bold text-gray-900">{{ count }}</span>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <p class="mt-2 text-sm">No asset data available</p>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Assets -->
            <div class="bg-white shadow-xl rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Recent Assets</h2>
                    <Link :href="route('assets.index')" 
                          class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        View All
                    </Link>
                </div>
                <div v-if="recentAssets && recentAssets.length > 0" class="space-y-3">
                    <div v-for="asset in recentAssets" :key="asset.id" 
                         class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ asset.asset_number }}</p>
                            <p class="text-xs text-gray-500">{{ asset.status }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="text-xs text-gray-500">{{ formatDate(asset.created_at) }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <p class="mt-2 text-sm">No recent assets</p>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="bg-white shadow-xl rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Recent Users</h2>
                    <Link :href="route('settings.users.index')" 
                          class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        View All
                    </Link>
                </div>
                <div v-if="recentUsers && recentUsers.length > 0" class="space-y-3">
                    <div v-for="user in recentUsers" :key="user.id" 
                         class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-green-600">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                            <p class="text-xs text-gray-500">{{ user.email }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="text-xs text-gray-500">{{ formatDate(user.created_at) }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                    <p class="mt-2 text-sm">No recent users</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';

const props = defineProps({
    dashboardData: {
        type: Object,
        required: true,
        default: () => ({ summary: [], tasks: [] })
    },
    userCountCard: {
        type: Number,
        default: 0
    },
    assetCountCard: {
        type: Number,
        default: 0
    },
    assetStats: {
        type: Object,
        default: () => ({})
    },
    assetStatusStats: {
        type: Object,
        default: () => ({})
    },
    recentAssets: {
        type: Array,
        default: () => []
    },
    recentUsers: {
        type: Array,
        default: () => []
    }
});

const formatDate = (date) => {
    return dayjs(date).format('MMM DD, YYYY');
};
</script>
