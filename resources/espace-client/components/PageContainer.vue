<script lang="ts" setup>
import { Head } from '@inertiajs/vue3';
defineProps({
    title: {
        type: String,
        default: '',
    },
});
</script>
<template>
    <main class="relative min-h-screen">
        <div class="absolute inset-0 -z-1 bg-pattern opacity-50"></div>
        <Head :title="title" />

        <slot />
    </main>
</template>
