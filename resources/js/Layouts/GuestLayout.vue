<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="min-h-screen flex flex-col items-center justify-center bg-no-repeat p-4"
        style="background-image: url('/assets/images/bg.png'); background-size: 100% 100%; background-position: center; background-repeat: no-repeat;"
    >
        <div class="w-full max-w-2xl overflow-hidden">
            <slot />
        </div>
        <div class="absolute bottom-0 left-0 right-0 flex items-end justify-between p-8">
            <div class="flex items-center">
                <img src="/assets/images/moh.png" alt="Government Logo" class="h-16 w-16 rounded-full" />
            </div>
            <div class="flex items-center">
                <img src="/assets/images/vista.png" alt="Government Logo" class="h-16 w-30 rounded-full" />
            </div>

            <div class="flex items-center">
                <img src="/assets/images/psi.png" alt="Government Logo" class="h-16 w-16" />
            </div>
        </div>
    </div>
</template>
