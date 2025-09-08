<template>
    <Head>
        <title>Districts</title>
    </Head>
    <AuthenticatedLayout>
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-semibold">Districts</h1>
                    <button @click="openModal()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        Add District
                    </button>
                </div>
                <!-- search -->
                <div class="mb-6">
                    <input type="text" v-model="search" placeholder="Search districts and region" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <!-- search -->

                <!-- Districts Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead class="bg-gray-100 sticky top-0">
                            <tr>
                                <th class="py-3 px-4 text-left border-b">ID</th>
                                <th class="py-3 px-4 text-left border-b">Name</th>
                                <th class="py-3 px-4 text-left border-b">Region</th>
                                <th class="py-3 px-4 text-left border-b">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="overflow-y-auto">
                            <tr v-for="district in districts.data" :key="district.id" class="hover:bg-gray-50">
                                <td class="py-3 px-4 border-b">{{ district.id }}</td>
                                <td class="py-3 px-4 border-b">{{ district.district_name }}</td>
                                <td class="py-3 px-4 border-b">{{ district.region_name }}</td>
                                <td class="py-3 px-4 border-b">
                                    <button @click="editDistrict(district)" class="px-3 py-1 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 mr-2">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="confirmDelete(district.id)" class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!districts || districts.length === 0">
                                <td colspan="4" class="py-4 px-4 text-center text-gray-500">No districts found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- District Modal -->
                <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4">
                        <div class="p-4 border-b">
                            <h2 class="text-xl font-semibold">{{ isEditing ? 'Edit District' : 'Add District' }}</h2>
                        </div>
                        <div class="p-4">
                            <form @submit.prevent="saveDistrict">
                                <div class="mb-4">
                                    <label for="district_name" class="block text-sm font-medium text-gray-700 mb-1">District Name</label>
                                    <input
                                        type="text"
                                        id="district_name"
                                        v-model="form.district_name"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required
                                    />
                                </div>

                                <div class="mb-4">
                                    <div class="flex justify-between items-center mb-1">
                                        <label for="region_name" class="block text-sm font-medium text-gray-700">Region</label>
                                        <button 
                                            type="button" 
                                            @click="showRegionForm = !showRegionForm"
                                            class="text-xs text-blue-600 hover:text-blue-800"
                                        >
                                            {{ showRegionForm ? 'Select Existing Region' : 'Add New Region' }}
                                        </button>
                                    </div>
                                    
                                    <!-- Region Dropdown -->
                                    <div v-if="!showRegionForm">
                                        <select
                                            id="region_name"
                                            v-model="form.region_name"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required
                                        >
                                            <option value="" disabled>Select a region</option>
                                            <option v-for="region in regions" :key="region.region_name" :value="region.region_name">
                                                {{ region.region_name }}
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <!-- Add New Region Form -->
                                    <div v-else>
                                        <input
                                            type="text"
                                            v-model="form.region_name"
                                            placeholder="Enter new region name"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required
                                        />
                                    </div>
                                </div>

                                <div class="flex justify-end mt-6 space-x-3">
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        :disabled="isSubmitting"
                                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                        :disabled="isSubmitting"
                                    >
                                        {{ isSubmitting ? 'Saving...' : 'Save' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import Swal from 'sweetalert2';

const toast = useToast();

const props = defineProps({
    districts: Object,
    regions: Array,
    filters: Object
});

// State
const showModal = ref(false);
const isEditing = ref(false);
const isSubmitting = ref(false);
const showRegionForm = ref(false);

// Form data - use reactive instead of ref to avoid nested .value references
const form = reactive({
    id: null,
    district_name: '',
    region_name: ''
});

const search = ref(props.filters?.search || "");

watch([
    () => search.value
], () => {
    reloadDistrict();
})

function reloadDistrict(){
    const query = {};
    if(search.value) query.search = search.value;
    router.get(route('districts.index'), query, {
        preserveState: true,
        preserveScroll: true,
        only: ['districts', 'regions']
    });
}

// Reset form
const resetForm = () => {
    form.id = null;
    form.district_name = '';
    form.region_name = '';
    isEditing.value = false;
    showRegionForm.value = false;
};

// Open modal
const openModal = () => {
    resetForm();
    showModal.value = true;
};

// Close modal
const closeModal = () => {
    showModal.value = false;
    resetForm();
};

// Edit district
const editDistrict = (district) => {
    isEditing.value = true;
    form.id = district.id;
    form.district_name = district.district_name;
    form.region_name = district.region_name;
    showModal.value = true;
};

// Save district
const saveDistrict = async () => {
    if (!form.district_name || !form.region_name) {
        toast.error('Please fill all required fields');
        return;
    }

    isSubmitting.value = true;
    await axios.post(route('districts.store'), form)
        .then((response) => {
            isSubmitting.value = false
            toast.success(response.data)
            closeModal()
            reloadDistrict()
        })
        .catch((error) => {
            isSubmitting.value = false
            toast.error(error.response.data)
        })
};


// Delete district
const confirmDelete = (id) => {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            deleteDistrict(id);
        }
    });
};

const deleteDistrict = async (id) => {
    try {
        const response = await axios.delete(route('districts.destroy', id));
        toast.success(response.data);
        reloadDistrict();
    } catch (error) {
        console.error('Error deleting district:', error);
        toast.error(error.response?.data || 'An error occurred while deleting the district');
    }
};

</script>