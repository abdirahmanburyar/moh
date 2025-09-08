<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from "axios";

const props = defineProps({
    subLocations: {
        required: true,
        type: Array
    },
    locations: {
        required: true,
        type: Array
    },
    filters: Object
});

const search = ref(props.filters?.search || "");

watch([
    () => search.value
], () => {
    reloadSubLocations();
});

function reloadSubLocations() {
    const query = {}
    if (search.value) query.search = search.value;
    router.get(route('assets.sub-locations.index'), query, {
        preserveScroll: true,
        preserveState: true,
        only: [
            "subLocations"
        ]
    });
}

const deleteSubLocation = async (id) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'You want to delete this sub-location?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.delete(route('assets.sub-locations.destroy', id));
            Swal.fire('Deleted!', response.data.message, 'success');
            router.reload();
        } catch (error) {
            Swal.fire('Error!', error.response?.data?.message || 'Failed to delete sub-location', 'error');
        }
    }
};

const getLocationName = (locationId) => {
    const location = props.locations.find(loc => loc.id === locationId);
    return location ? location.name : 'Unknown Location';
};
</script>

<template>
    <AuthenticatedLayout title="Asset Sub-Locations" description="Manage your asset sub-locations">
        <div class="flex justify-between items-center mb-6">
            <div class="flex flex-col">
                <Link :href="route('assets.index')" class="text-gray-500 hover:text-gray-700">
                Back to Assets
                </Link>
                <h2 class="text-2xl font-semibold text-gray-900">Asset Sub-Locations</h2>
                <div class="relative w-full">
                    <input type="text" v-model="search"
                        class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Search sub-locations...">
                    <button type="button"
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 bg-white rounded-full p-1 hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        @click="reloadSubLocations">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
            </div>
            <Link :href="route('assets.sub-locations.create')"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            Add New Sub-Location
            </Link>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden mb-[60px]">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Sub-Location Name
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Main Location
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="subLocation in subLocations" :key="subLocation.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ subLocation.name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ getLocationName(subLocation.asset_location_id) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-3">

                            <button @click="router.visit(route('assets.sub-locations.edit', subLocation.id))"
                                class="text-indigo-600 hover:text-indigo-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>

                            <button
                                class="text-red-600 hover:text-red-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>

                        </td>
                    </tr>
                    <tr v-if="subLocations.length === 0">
                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                            No sub-locations found. Create one to get started.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>
