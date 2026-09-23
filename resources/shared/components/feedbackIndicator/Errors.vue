<template>
    <p v-for="(error, idx) in listError" :key="idx" class="flex mt-1 text-2xs text-red-600 gap-2">
        <AlertDiamondIcon class="w-4" />
        <span class="flex-1 leading-4">{{ error }}</span>
    </p>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { AlertDiamondIcon } from '@adersolutions/icons';

const porps = defineProps({
    errors: {
        type: [String, Array],
        default: () => [],
    },
});

const listError = computed(() => {
    if (typeof porps.errors === 'string') {
        return [porps.errors];
    }
    return porps.errors;
});
</script>
