<template>
    <Head title="Access Denied" />
    
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
        <div class="max-w-2xl w-full">
            <!-- Header with Logo Area -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-6">
                    <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-3">Access Denied</h1>
                <p class="text-lg text-gray-600">You don't have permission to access this resource</p>
            </div>

            <!-- Main Content Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                <!-- Status Bar -->
                <div class="bg-red-50 border-b border-red-200 px-6 py-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Permission Required</h3>
                            <p class="text-sm text-red-700 mt-1">
                                This action requires specific permissions that your account doesn't have
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-6">
                    <div v-if="permission" class="mb-6">
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">Required Permission</h4>
                                    <p class="text-sm text-gray-600 mt-1 font-mono bg-gray-100 px-2 py-1 rounded">{{ permission }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resolution Steps -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900">How to resolve this:</h3>
                        
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mt-0.5">
                                    <span class="text-blue-600 text-xs font-semibold">1</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-gray-700">
                                        <strong>Contact your administrator</strong> to request the required permissions
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mt-0.5">
                                    <span class="text-blue-600 text-xs font-semibold">2</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-gray-700">
                                        <strong>Return to Home</strong> to access other parts of the system
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mt-0.5">
                                    <span class="text-blue-600 text-xs font-semibold">3</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-gray-700">
                                        <strong>Check your account</strong> - you might be logged in with the wrong user
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <Link 
                            :href="route('dashboard')" 
                            class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-blue-600 border border-transparent rounded-lg font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Return to Home
                        </Link>
                        
                        <Link 
                            :href="route('assets.index')" 
                            class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-white border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Assets Page
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <p class="text-sm text-gray-500">
                    If you believe this is an error, please contact your system administrator
                </p>
                <p class="text-xs text-gray-400 mt-2">
                    Error Code: 403 Forbidden
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    permission: {
        type: String,
        default: null
    }
});
</script>
