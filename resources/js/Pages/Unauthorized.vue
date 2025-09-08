<template>
    <Head title="Unauthorized Access" />
    <AuthenticatedLayout :title="'Unauthorized Access'" :description="'You do not have permission to access this page'">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 flex flex-col items-center">
                        <div class="text-center mb-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-red-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <h1 class="text-3xl font-bold text-red-600">Access Denied</h1>
                            <p class="text-lg text-gray-600 mt-2">You do not have permission to access this page.</p>
                        </div>
                        
                        <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 w-full max-w-2xl">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">What can you do?</h2>
                            <ul class="list-disc list-inside space-y-2 text-gray-700">
                                <li>Return to the <a :href="route('dashboard')" class="text-blue-600 hover:underline">dashboard</a></li>
                                <li>Contact your system admin to request access</li>
                                <li>Check if you're logged in with the correct account</li>
                            </ul>
                        </div>
                        
                        <div class="mt-8">
                            <Link :href="route('dashboard')" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Return to Dashboard
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
</script>
