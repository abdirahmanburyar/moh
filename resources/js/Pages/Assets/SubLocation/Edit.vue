<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import Swal from "sweetalert2";
import axios from "axios";

const props = defineProps({
    subLocation: {
        required: true,
        type: Object
    },
    locations: {
        required: true,
        type: Array
    }
});

const form = ref({
    name: props.subLocation.name,
    asset_location_id: props.subLocation.asset_location_id,
    asset_location: props.subLocation?.location,
});

const isSubmitting = ref(false);

const submit = async () => {
    if (isSubmitting.value) return;
    
    const result = await Swal.fire({
        title: 'Update Sub-Location',
        text: 'Are you sure you want to update this sub-location?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, update it!'
    });

    if (result.isConfirmed) {
        isSubmitting.value = true;
        try {
            const response = await axios.post(route('assets.sub-locations.store'), form.value);
            isSubmitting.value = false;
            Swal.fire('Success!', 'Sub-location updated successfully', 'success')
                .then(() => {
                    router.visit(route('assets.sub-locations.index'));
                });
        } catch (error) {
            isSubmitting.value = false;
            Swal.fire('Error!', error.response?.data?.message || 'Failed to update sub-location', 'error');
        }
    }
};

function handleCategorySelect(selected) {
    if(!selected){
        form.value.asset_location = null;
        form.value.asset_location_id = "";
        return;
    }
    form.value.asset_location = selected;
    form.value.asset_location_id = selected.id;
}

</script>

<template>
    <AuthenticatedLayout title="Edit Sub-Location" description="Edit an existing sub-location">
        <div class="max-w-2xl mx-auto py-6">
            <div class="flex items-center justify-between mb-6">
                <Link :href="route('assets.sub-locations.index')" class="text-gray-500 hover:text-gray-700">
                    Back to Sub-Locations
                </Link>

                <h2 class="text-2xl font-semibold text-gray-900">Edit Sub-Location</h2>
            </div>

            <div class="bg-white rounded-lg shadow">
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Sub-Location Name</label>
                        <input type="text" id="name" v-model="form.name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Enter sub-location name" required />
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700">Main Location</label>
                        <Multiselect v-model="form.asset_location" :value="form.asset_location_id"
                            :options="props.locations" :searchable="true" :close-on-select="true" :show-labels="false"
                            :allow-empty="true" placeholder="Select Location" track-by="id" label="name"
                            @select="handleCategorySelect"></Multiselect>
                    </div>

                    <div class="flex items-center justify-end space-x-3">
                        <button type="button" @click="router.visit(route('assets.sub-locations.index'))"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Exit
                        </button>
                        <button type="submit"
                            :disabled="isSubmitting"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                            {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
