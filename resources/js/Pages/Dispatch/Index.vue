<template>
    <AuthenticatedLayout>
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Order Dispatching</h2>
            <div class="flex gap-4">
                <select v-model="selectedFacility"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">All Facilities</option>
                    <option v-for="facility in props.facilities" :key="facility.id" :value="facility.id">
                        {{ facility.name }}
                    </option>
                </select>
                <select v-model="selectedStatus"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">All Statuses</option>
                    <option value="approved">Approved</option>
                    <option value="in process">In Process</option>
                    <option value="dispatched">Dispatched</option>
                </select>
            </div>
        </div>
        <div class="py-6">
            <!-- Stats Section -->
        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <div class="text-lg font-semibold">Pending Orders</div>
                <div class="text-3xl font-bold text-blue-600">{{ pendingCount }}</div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <div class="text-lg font-semibold">In Process</div>
                    <div class="text-3xl font-bold text-yellow-600">{{ inProcessCount }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-lg font-semibold">Dispatched Today</div>
                    <div class="text-3xl font-bold text-green-600">{{ dispatchedTodayCount }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-lg font-semibold">Total Items</div>
                    <div class="text-3xl font-bold text-purple-600">{{ totalItems }}</div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Order #
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Facility
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Items
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created At
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #{{ order.id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ order.facility.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button @click="showOrderItems(order)"
                                            class="text-indigo-600 hover:text-indigo-900">
                                            View {{ order.items.length }} Items
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusClass(order.status)"
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ order.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(order.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button @click="showDispatchModal(order)"
                                            :disabled="order.status === 'dispatched'" :class="[
                                                'inline-flex items-center px-4 py-2 border rounded-md text-sm font-medium',
                                                order.status === 'dispatched'
                                                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                    : 'bg-indigo-600 text-white hover:bg-indigo-700'
                                            ]">
                                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Dispatch
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        <Pagination :links="props.orders.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items Modal -->
        <Modal :show="itemsModalOpen" @close="itemsModalOpen = false" maxWidth="4xl">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Order Items</h3>
                    <div class="flex items-center gap-4">
                        <button v-if="hasSelectedItems" @click="dispatchSelectedItems"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Dispatch Selected
                            <span v-if="selectedItems.length" class="ml-2 bg-indigo-500 px-2 py-1 rounded-full text-xs">
                                {{ selectedItems.length }}
                            </span>
                        </button>
                        <button @click="dispatchAllItems"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Dispatch All
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                </th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Product</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Quantity</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Batch #</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="item in selectedOrderItems" :key="item.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" v-model="selectedItems" :value="item.id"
                                        :disabled="item.status === 'dispatched'"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.product.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.quantity }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.batch_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getStatusClass(item.status)"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button v-if="item.status !== 'dispatched'" @click="dispatchSingleItem(item)"
                                        class="text-indigo-600 hover:text-indigo-900">
                                        Dispatch
                                    </button>
                                    <span v-else class="text-gray-400">Dispatched</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 flex justify-end">
                    <button @click="itemsModalOpen = false"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref, computed } from 'vue';
import { format } from 'date-fns';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    orders: Object,
    facilities: Array,
    stats: Object,
    warehouses: Array
});

// State
const selectedFacility = ref('');
const selectedStatus = ref('');
const itemsModalOpen = ref(false);
const selectedOrderItems = ref([]);
const selectedItems = ref([]);
const selectAll = ref(false);
const currentOrder = ref(null);

// Computed
const filteredOrders = computed(() => {
    let filtered = props.orders.data;

    if (selectedFacility.value) {
        filtered = filtered.filter(order => order.facility.id === selectedFacility.value);
    }

    if (selectedStatus.value) {
        filtered = filtered.filter(order => order.status === selectedStatus.value);
    }

    return filtered;
});

const pendingCount = computed(() => props.stats?.pending || 0);
const inProcessCount = computed(() => props.stats?.in_process || 0);
const dispatchedTodayCount = computed(() => props.stats?.dispatched_today || 0);
const totalItems = computed(() => props.stats?.total_items || 0);

const hasSelectedItems = computed(() => selectedItems.value.length > 0);

// Methods
const formatDate = (date) => {
    return format(new Date(date), 'MMM dd, yyyy HH:mm');
};

const getStatusClass = (status) => {
    const classes = {
        'approved': 'bg-green-100 text-green-800',
        'in process': 'bg-yellow-100 text-yellow-800',
        'dispatched': 'bg-blue-100 text-blue-800',
        'pending': 'bg-gray-100 text-gray-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedItems.value = selectedOrderItems.value
            .filter(item => item.status !== 'dispatched')
            .map(item => item.id);
    } else {
        selectedItems.value = [];
    }
};

const showOrderItems = (order) => {
    selectedOrderItems.value = order.items;
    currentOrder.value = order;
    selectedItems.value = [];
    selectAll.value = false;
    itemsModalOpen.value = true;
};

const processDispatch = async (itemIds) => {
    const { value: warehouseId } = await Swal.fire({
        title: 'Select Warehouse',
        html: `
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">Order #${currentOrder.value.id} for ${currentOrder.value.facility.name}</p>
                <p class="text-sm text-gray-600">Items to dispatch: ${itemIds.length}</p>
            </div>
            <select id="warehouse-select" class="swal2-select">
                <option value="">Select a warehouse</option>
                ${props.warehouses.map(w => `
                    <option value="${w.id}">${w.name}</option>
                `).join('')}
            </select>
        `,
        showCancelButton: true,
        confirmButtonText: 'Dispatch',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const select = document.getElementById('warehouse-select');
            const warehouseId = select.value;
            if (!warehouseId) {
                Swal.showValidationMessage('Please select a warehouse');
                return false;
            }
            return warehouseId;
        }
    });

    if (warehouseId) {
        try {
            await router.post(route('dispatch.process'), {
                order_id: currentOrder.value.id,
                item_ids: itemIds,
                warehouse_id: warehouseId
            });

            Swal.fire({
                title: 'Success!',
                text: 'Items have been dispatched successfully',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            // Refresh the page to update the order list
            router.reload();
        } catch (error) {
            Swal.fire({
                title: 'Error!',
                text: error.response?.data || 'Something went wrong',
                icon: 'error'
            });
        }
    }
};

const dispatchSingleItem = (item) => {
    processDispatch([item.id]);
};

const dispatchSelectedItems = () => {
    if (selectedItems.value.length === 0) {
        Swal.fire({
            title: 'No items selected',
            text: 'Please select items to dispatch',
            icon: 'warning'
        });
        return;
    }
    processDispatch(selectedItems.value);
};

const dispatchAllItems = () => {
    const dispatchableItems = selectedOrderItems.value
        .filter(item => item.status !== 'dispatched')
        .map(item => item.id);

    if (dispatchableItems.length === 0) {
        Swal.fire({
            title: 'No items to dispatch',
            text: 'All items have already been dispatched',
            icon: 'warning'
        });
        return;
    }

    processDispatch(dispatchableItems);
};

const showDispatchModal = async (order) => {
    const { value: warehouseId } = await Swal.fire({
        title: 'Select Warehouse',
        html: `
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">Order #${order.id} for ${order.facility.name}</p>
                <p class="text-sm text-gray-600">Total Items: ${order.items.length}</p>
            </div>
            <select id="warehouse-select" class="swal2-select">
                <option value="">Select a warehouse</option>
                ${props.warehouses.map(w => `
                    <option value="${w.id}">${w.name}</option>
                `).join('')}
            </select>
        `,
        showCancelButton: true,
        confirmButtonText: 'Dispatch',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const select = document.getElementById('warehouse-select');
            const warehouseId = select.value;
            if (!warehouseId) {
                Swal.showValidationMessage('Please select a warehouse');
                return false;
            }
            return warehouseId;
        }
    });

    if (warehouseId) {
        try {
            await router.post(route('dispatch.process'), {
                order_id: order.id,
                warehouse_id: warehouseId
            });

            Swal.fire({
                title: 'Success!',
                text: 'Order has been dispatched successfully',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            // Refresh the page to update the order list
            router.reload();
        } catch (error) {
            Swal.fire({
                title: 'Error!',
                text: error.response?.data || 'Something went wrong',
                icon: 'error'
            });
        }
    }
};
</script>
