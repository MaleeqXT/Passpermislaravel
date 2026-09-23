<script setup lang="ts">
import type { ReservationType } from '@common/types';
import { computed } from 'vue';

type PropsType = {
    count?: number;
    item?: Partial<ReservationType & { disabled_for?: any; all?: boolean | undefined }>;
};
const props = withDefaults(defineProps<PropsType>(), {
    count: 0,
});
const datehour = computed(() => props.item?.date_hour || parseInt(props.item?.start_at || '0'));
</script>
<template>
    <li :class="['relative z-1 p-1 col-start-' + (count + 1)]" :style="{ gridRow: `${datehour - 5}/ span ${item?.hour || 1}` }">
        <slot />
    </li>
</template>
