<script setup lang="ts">
import { InputField } from '../selectionAndForms';

defineProps({
    types: {
        type: Array,
        default: () => ['gif', 'jpeg', 'png', 'webp', 'svg', 'jpg'],
    },
});
const emit = defineEmits(['change']);
</script>

<template>
    <div class="flex-center w-full h-full flex-col">
        <div class="max-w-xl m-5 w-full">
            <InputField label="Insert url" />
        </div>
    </div>
</template>
