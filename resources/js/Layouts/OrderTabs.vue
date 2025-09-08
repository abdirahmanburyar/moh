<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    currentTab: {
        type: String,
        required: true
    }
});

const tabs = [
    { name: 'All Orders', route: 'orders.index', key: 'index' },
    { name: 'Pending', route: 'orders.pending', key: 'pending' },
    { name: 'Approved', route: 'orders.approved', key: 'approved' },
    { name: 'In Process', route: 'orders.in-process', key: 'in_process' },
    { name: 'Dispatched', route: 'orders.dispatched', key: 'dispatched' },
    { name: 'Delivered', route: 'orders.delivered', key: 'delivered' },
    { name: 'Received', route: 'orders.received', key: 'received' }
];
</script>

<template>
    <div class="bg-white mb-[80px]">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex space-x-8">
                    <Link
                        v-for="tab in tabs"
                        :key="tab.name"
                        :href="route(tab.route)"
                        :class="[
                            currentTab === tab.key
                                ? 'border-indigo-500 text-gray-900'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                            'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium'
                        ]"
                    >
                        {{ tab.name }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
    <main class="mb-[100px]">
        <slot />
    </main>
</template>
