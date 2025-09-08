<script setup>
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    error: {
        type: String,
    },
});

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const isLoading = ref(false);
const showPassword = ref(false);
const loginAnimation = ref(false);

const submit = () => {
    if (form.processing) return;
    
    isLoading.value = true;
    loginAnimation.value = true;
    
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
            isLoading.value = false;
            setTimeout(() => {
                loginAnimation.value = false;
            }, 500);
        },
    });
};

// Toggle password visibility
const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="flex items-center justify-center">
            <div 
                class="w-full md:w-2/4 max-w-md bg-white p-8 transition-all duration-300"
            >
                <div class="text-center mb-6">
                    <h1 class="text-lg font-bold text-gray-800">Sign In</h1>
                    <p class="text-gray-600 text-sm">Let's get you Dive in to VISTA</p>
                </div>
                
                <div v-if="status" class="bg-green-50 border-l-4 border-green-400 p-3 mb-4 rounded text-green-700 font-medium">
                    {{ status }}
                </div>

                <div v-if="error" class="bg-red-50 border-l-4 border-red-400 p-3 mb-4 rounded text-red-700 font-medium">
                    {{ error }}
                </div>

                <form @submit.prevent="submit" class="flex flex-col gap-4">
                    <div>
                        <InputLabel for="username" value="Username" class="font-semibold text-gray-700 mb-1 block" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <input
                                id="username"
                                type="text"
                                class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-sm placeholder-gray-400 disabled:bg-gray-100"
                                v-model="form.username"
                                required
                                autofocus
                                autocomplete="username"
                                :disabled="isLoading"
                                placeholder="Enter your username"
                            />
                        </div>
                        <InputError class="mt-2" :message="form.errors.username" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Password" class="font-semibold text-gray-700 mb-1 block" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-200 transition-all duration-200 text-sm placeholder-gray-400 disabled:bg-gray-100"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                :disabled="isLoading"
                                placeholder="Enter your password"
                            />
                            <button 
                                type="button" 
                                @click="togglePasswordVisibility"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors duration-200"
                                :disabled="isLoading"
                            >
                                <svg v-if="!showPassword" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"></path>
                                    <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"></path>
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between mt-2">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" :disabled="isLoading" />
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
                        <!-- <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs text-blue-600 hover:underline ml-2"
                        >
                            Forgot your password?
                        </Link> -->
                    </div>

                    <PrimaryButton
                        class="w-full py-3 rounded-lg font-semibold bg-gradient-to-r from-blue-500 to-blue-400 text-white shadow hover:from-blue-600 hover:to-blue-500 transition disabled:opacity-60 flex items-center justify-center"
                        :disabled="form.processing || isLoading"
                    >
                        <span v-if="isLoading" class="flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Please Wait...
                        </span>
                        <span v-else>Log in</span>
                    </PrimaryButton>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
