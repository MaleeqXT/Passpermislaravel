<script setup lang="ts">
import { computed } from 'vue';
import { strip } from '@shared/utils';

interface PropsType {
    done: number;
    total: number;
}

const props = defineProps<PropsType>();

const totalDashes = 10;
const state = computed(() => {
    const percentage = !props.done || !props.total ? 0 : strip((props.done / props.total) * 100);
    const filled = !percentage ? 0 : Math.floor((percentage / 100) * totalDashes);
    return {
        filled,
        partialFill: (percentage / 100) * totalDashes > filled,
        color: percentage < 33.33 ? 'red-500' : percentage < 66.66 ? 'yellow-500' : 'primary',
    };
});
// from-red-500 to-red-500 from-yellow-500 to-yellow-500 from-primary to-primary via-primary via-red-500 via-yellow-500
</script>

<template>
    <ul class="flex gap-1 pt-1">
        <li
            v-for="(dash, index) in totalDashes"
            :key="index"
            :class="[
                'flex-1 h-2 rounded ',
                index < state.filled ? 'bg-' + state.color : 'bg-gray-500/30',
                index === state.filled && state.partialFill ? '!bg-gradient-to-r to-60% to-gray-500/30 from-' + state.color : '',
            ]"
        />
    </ul>
</template>
