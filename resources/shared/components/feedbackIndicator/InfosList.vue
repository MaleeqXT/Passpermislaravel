<template>
    <p v-for="(item, idx) in items" :key="idx" class="flex text-2xs text-gray-600 gap-2">
        <component :is="item?.icon || InfoIcon" v-if="typeof item === 'object'" class="w-4" />
        <span class="flex-1 leading-4">{{ typeof item === 'object' ? item.content : item }}</span>
    </p>
</template>

<script setup lang="ts">
import { InfoIcon } from '@adersolutions/icons';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});
</script>
