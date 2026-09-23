<template>
    <li class="bg-white rounded-[30px] p-4 md:p-9"></li>
</template>

<script setup lang="ts">
defineProps({
    item: {
        type: Object,
        default: () => ({}),
    },
});
</script>
