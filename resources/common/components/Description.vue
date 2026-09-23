<template>
    <Popup v-if="text" position="top" hoverable prevent-focus :delay="500">
        <p class="max-w-80 line-clamp-2 text-wrap">{{ text }}</p>
        <template #content>
            <p class="prose max-w-md p-2 text-sm">{{ text }}</p>
        </template>
    </Popup>
</template>

<script setup lang="ts">
import { Popup } from '@shared/components';

defineProps({
    text: String,
});
</script>
