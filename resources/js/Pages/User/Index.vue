<template>
    <UserAuthTab>
        <Head title="User Management" />

        <!-- Header Section -->
        <div class="bg-white shadow-xl rounded-2xl mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-white/20 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-10 h-10 text-white">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">User Management</h1>
                            <p class="text-blue-100 text-sm mt-1">
                                Manage system users, roles, and permissions
                            </p>
                        </div>
                    </div>
                    <div class="mt-6 sm:mt-0">
                        <Link
                            :href="route('settings.users.create')"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add New User
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900">{{ users.total || 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Users</p>
                        <p class="text-2xl font-bold text-gray-900">{{ users.data?.filter(u => u.is_active).length || 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Inactive Users</p>
                        <p class="text-2xl font-bold text-gray-900">{{ users.data?.filter(u => !u.is_active).length || 0 }}</p>
                    </div>
                </div>
            </div>


        </div>

        <!-- Filters Section -->
        <div class="bg-white shadow-xl rounded-2xl mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Users</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by name, email, username..."
                                class="pl-10 pr-4 py-3 w-full text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            />
                        </div>
                    </div>
                </div>
                
                <div class="w-full flex justify-end">
                    <select 
                    v-model="per_page" 
                    @change="props.filters.page = 1"
                    class="w-[200px] mt-4 flex justify-end px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                >
                    <option value="10">10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                </select>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                User
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Contact
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Title
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Permissions
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 transition-colors">
                            <!-- User Info -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center">
                                            <span class="text-sm font-medium text-white">{{ user.name.charAt(0).toUpperCase() }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900">{{ user.name }}</div>
                                        <div class="text-sm text-gray-500">{{ user.title || 'No title' }}</div>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Contact Info -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ user.username }}</div>
                                <div class="text-sm text-gray-500">{{ user.email }}</div>
                            </td>
                            
                            <!-- Title -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ user.title || 'No title' }}</div>
                            </td>
                            
                            <!-- Permissions -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <span v-if="user.permissions && user.permissions.length > 0" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ user.permissions.length }} permission{{ user.permissions.length !== 1 ? 's' : '' }}
                                    </span>
                                    <span v-else class="text-gray-500">No permissions</span>
                                </div>
                            </td>
                            
                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                >
                                    <span class="w-2 h-2 rounded-full mr-1.5"
                                        :class="user.is_active ? 'bg-green-400' : 'bg-red-400'"
                                    ></span>
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            
                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <Link 
                                        :href="route('settings.users.edit', user.id)"
                                        class="inline-flex items-center p-2 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded-lg transition-colors"
                                        title="Edit User"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    
                                    <button @click="confirmToggleStatus(user)" class="relative inline-flex items-center cursor-pointer" :title="user.is_active ? 'Deactivate User' : 'Activate User'">
                                        <div class="w-10 h-5 rounded-full after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all" 
                                            :class="{ 
                                                'bg-green-500 after:translate-x-full after:border-white': user.is_active, 
                                                'bg-red-500': !user.is_active 
                                            }"></div>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-if="!users.data || users.data.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new user.</p>
                <div class="mt-6">
                    <Link
                                                    :href="route('settings.users.create')"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add User
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="users.data && users.data.length > 0" class="bg-gray-50 px-6 py-3 border-t border-gray-200">
                <div class="flex items-center justify-end">
                    <TailwindPagination 
                        :data="users" 
                        @pagination-change-page="getUsers"
                        :limit="2"
                    />
                </div>
            </div>
        </div>
    </UserAuthTab>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import Swal from 'sweetalert2';
import UserAuthTab from '@/Layouts/UserAuthTab.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Link, Head } from '@inertiajs/vue3';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import { TailwindPagination } from 'laravel-vue-pagination';

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
});

const toast = useToast();

const search = ref(props.filters?.search || '');
const processing = ref(false);
const per_page = ref(props.filters?.per_page || '10');
const role = ref(props.filters?.role || null);
const status = ref(props.filters?.status || 'All');


// Watch for filter changes
watch(
    () => [role.value, search.value, status.value, props.filters.page, per_page.value],
    () => {
        applyFilters();
    });

// Apply filters
const applyFilters = () => {
     // Start with base parameters
     const params = {};
    
    // Only add search if it has a value
    if (search.value) {
        params.search = search.value;
    }
    
    // Only add role_id if it has a value and is not the 'All' option
    if (role.value) {
        params.role = role.value;
    }

    // Only add status if it has a value and is not the 'All' option
    if (status.value) {
        params.status = status.value;
    }

    if (per_page.value) {
        params.per_page = per_page.value;
    }

    if (props.filters.page) {
        params.page = props.filters.page;
    }

            router.get(route('settings.users.index'), params, {
        preserveState: true,
        preserveScroll: true,
        only: ['users', 'roles'],
    });
};

// Get users for pagination
const getUsers = (page) => {
    props.filters.page = page;
};

// Confirm toggle user status with SweetAlert
const confirmToggleStatus = (user) => {
    const newStatus = !user.is_active;
    const action = newStatus ? 'activate' : 'deactivate';
    
    Swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} User?`,
        text: `Are you sure you want to ${action} ${user.name}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: newStatus ? '#10B981' : '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: `Yes, ${action} user!`
    }).then((result) => {
        if (result.isConfirmed) {
            toggleUserStatus(user, newStatus);
        }
    });
};

// Toggle individual user status
const toggleUserStatus = async (user, newStatus) => {
    try {
        processing.value = true;
        
        const response = await axios.post(route('settings.users.toggle-status'), {
            user_id: user.id,
            is_active: newStatus
        });
        
        if (response.data.success) {
            // Update the user status locally to avoid a full page reload
            user.is_active = newStatus;
            
            Swal.fire({
                title: 'Success!',
                text: response.data.message,
                icon: 'success',
                confirmButtonColor: '#10B981'
            });
        } else {
            throw new Error(response.data.message || 'Failed to update user status');
        }
    } catch (error) {
        Swal.fire({
            title: 'Error!',
            text: error.message || 'Failed to update user status',
            icon: 'error',
            confirmButtonColor: '#EF4444'
        });
        console.error(error);
    } finally {
        processing.value = false;
    }
};
</script>
