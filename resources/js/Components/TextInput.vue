<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        required: true
    },
    type: {
        type: String,
        default: 'text'
    }
});


const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        :type="type"
        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        ref="input"
    />
</template>
