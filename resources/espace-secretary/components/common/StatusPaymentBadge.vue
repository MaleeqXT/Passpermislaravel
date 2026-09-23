<template>
    <span
        :class="status.color"
        class="text-xs rounded-full px-2 py-1 inline-block whitespace-nowrap text-center align-baseline font-bold uppercase leading-none text-white"
    >
        {{ status.name }}
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { STATUS_PAYMENT } from '@shared/constants/statusPayment';

const props = defineProps({
    statusIndex: [String, Number],
});

const status = computed(() => {
    return STATUS_PAYMENT.find(({ id }) => props.statusIndex === id);
});
</script>
