<template>
    <div>
        <SplashScreen v-if="showSplash" @complete="onSplashComplete" />
        <div v-else>
            <component :is="app"></component>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import SplashScreen from '@/Components/SplashScreen.vue';

const props = defineProps({
    app: Object,
});

const showSplash = ref(true);
const hasSeenSplash = computed(() => {
    return localStorage.getItem('hasSeenSplash') === 'true';
});

onMounted(() => {
    // Skip splash screen if already seen in this session
    if (hasSeenSplash.value) {
        showSplash.value = false;
    }
});

const onSplashComplete = () => {
    showSplash.value = false;
    localStorage.setItem('hasSeenSplash', 'true');
    
    // Reset the splash screen after 30 minutes of inactivity
    const resetSplashAfterInactivity = () => {
        let inactivityTimer;
        
        const resetTimer = () => {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                localStorage.removeItem('hasSeenSplash');
            }, 30 * 60 * 1000); // 30 minutes
        };
        
        // Reset timer on user activity
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(
            event => document.addEventListener(event, resetTimer, false)
        );
        
        resetTimer();
    };
    
    resetSplashAfterInactivity();
};
</script>
