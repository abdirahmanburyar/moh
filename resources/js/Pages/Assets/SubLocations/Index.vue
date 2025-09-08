<template>
    <AuthenticatedLayout title="Asset Sub-Locations" description="Manage asset sub-locations" img="/assets/images/asset.png">
        <div class="container-fluid py-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Asset Sub-Locations</h5>
                    <Link :href="route('assets.index')" class="btn btn-sm btn-outline-secondary">
                        Back to Assets
                    </Link>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Parent Location</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(subLocation, index) in subLocations" :key="subLocation.id">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ subLocation.name }}</td>
                                    <td>{{ subLocation.location ? subLocation.location.name : 'N/A' }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary">Edit</button>
                                            <button class="btn btn-outline-danger">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!subLocations.length">
                                    <td colspan="4" class="text-center py-3">No sub-locations found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    subLocations: Array,
    locations: Array
});
</script>
