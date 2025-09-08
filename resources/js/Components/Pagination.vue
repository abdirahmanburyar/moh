<template>
    <div v-if="links && links.length > 0" class="flex items-center justify-between">
        <div class="text-sm text-gray-700">
            Showing {{ meta.from }} to {{ meta.to }} of {{ meta.total }} results
        </div>
        <div class="flex space-x-1">
            <Link 
                v-for="link in links" 
                :key="link.label"
                :href="link.url" 
                :class="[
                    'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                    link.active 
                        ? 'bg-blue-600 text-white' 
                        : link.url === null
                            ? 'text-gray-400 cursor-not-allowed'
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'
                ]"
                :disabled="link.url === null"
                v-html="link.label"
            />
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: {
        type: Array,
        required: true,
        default: () => []
    },
    meta: {
        type: Object,
        required: true,
        default: () => ({})
    }
});
</script>
