<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from "axios";

const props = defineProps({
    locations: {
        required: true,
        type: Array
    },
    filters: Object
});

const showSubLocations = ref(false);
const selectedLocation = ref(null);
const subLocations = ref([]);

const search = ref(props.filters.search || "");

const fetchSubLocations = async (locationId) => {
    try {
        const response = await axios.get(route('assets.locations.sub-locations', locationId));
        subLocations.value = response.data;
        showSubLocations.value = true;
        selectedLocation.value = locationId;
    } catch (error) {
        console.error('Error fetching sub-locations:', error);
    }
};

watch([
    () => search.value
], () => {
    reloadLocation();
});

function reloadLocation(){
    const query = {}
    if(search.value) query.search = search.value;
    router.get(route('assets.locations.index'), query, {
        preserveScroll: true,
        preserveState: true,
        only: [
            "locations"
        ]
    });
}

const deleteLocation = async (id) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'You want to delete this location?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.delete(route('assets.locations.destroy', id));
            Swal.fire('Deleted!', response.data.message, 'success');
            router.reload();
        } catch (error) {
            Swal.fire('Error!', error.response?.data?.message || 'Failed to delete location', 'error');
        }
    }
};
</script>

<template>
    <AuthenticatedLayout title="Asset Locations" description="Manage your asset locations">
        <div class="flex justify-between items-center mb-6">
            <div class="flex flex-col">
                <Link :href="route('assets.index')" class="text-gray-500 hover:text-gray-700">
                    Back to Assets
                </Link>
                <h2 class="text-2xl font-semibold text-gray-900">Asset Locations</h2>
                <div class="relative w-full">
                    <input type="text" v-model="search"
                        class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Search locations...">
                    <button type="button"
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 bg-white rounded-full p-1 hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        @click="reloadLocation">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
            </div>
            <Link :href="route('assets.locations.create')"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Add New Location
            </Link>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden mb-[60px]">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Location Name
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Sub-locations
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="location in locations" :key="location.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ location.name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button @click="fetchSubLocations(location.id)"
                                class="text-indigo-600 hover:text-indigo-900">View Sub-locations</button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-3">
                            <Link :href="route('assets.locations.edit', location.id)"
                                class="text-indigo-600 hover:text-indigo-900">Edit</Link>
                            <button @click="deleteLocation(location.id)"
                                class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="locations.length === 0">
                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                            No locations found. Create one to get started.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Sub-locations Modal -->
        <div v-if="showSubLocations" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full"
            @click="showSubLocations = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"
                @click.stop>
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Sub-locations</h3>
                    <div class="mt-2 space-y-2">
                        <div v-for="subLocation in subLocations" :key="subLocation.id"
                            class="flex justify-between items-center p-2 bg-gray-50 rounded">
                            <span>{{ subLocation.name }}</span>
                            <button @click="deleteSubLocation(subLocation.id)"
                                class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                        </div>
                        <div v-if="subLocations.length === 0" class="text-center text-gray-500 text-sm">
                            No sub-locations found
                        </div>
                    </div>
                    <div class="mt-4">
                        <button class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md"
                            @click="showSubLocations = false">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>