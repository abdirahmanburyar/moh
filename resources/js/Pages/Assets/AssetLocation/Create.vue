<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from "sweetalert2";
import axios from "axios";

const form = ref({
    name: '',
});

const isSubmitting = ref(false);

const submit = async () => {
    if (isSubmitting.value) return;
    
    const result = await Swal.fire({
        title: 'Create Location',
        text: 'Are you sure you want to create this location?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, create it!'
    });

    if (result.isConfirmed) {
        isSubmitting.value = true;
        const response = await axios.post(route('assets.locations.store'), form.value)
            .then((response) => {
                isSubmitting.value = false;
                Swal.fire('Success!', 'Location created successfully', 'success')
                    .then(() => {
                        router.visit(route('assets.locations.index'));
                    });
            })
            .catch((error) => {
                isSubmitting.value = false;
                Swal.fire('Error!', error.response?.data || 'Failed to create location', 'error');

            });
    }
};
</script>

<template>
    <AuthenticatedLayout title="Create Asset Location" description="Create a new asset location">
        <div class="max-w-2xl mx-auto py-6">
            <div class="flex items-center justify-between mb-6">
                <Link :href="route('assets.locations.index')" class="text-gray-500 hover:text-gray-700">
                    Back to Locations
                </Link>

                <h2 class="text-2xl font-semibold text-gray-900">Create Asset Location</h2>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Location Name</label>
                        <input type="text" id="name" v-model="form.name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Enter location name" required />
                    </div>

                    <div class="flex items-center justify-end space-x-3">
                        <button type="button" @click="router.visit(route('assets.locations.index'))"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Exit
                        </button>
                        <button type="submit"
                            :disabled="isSubmitting"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                            {{ isSubmitting ? 'Creating...' : 'Create Location' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>