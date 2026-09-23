<script setup lang="ts">
import { ColorPicker } from 'vue3-colorpicker'; // Import ColorPicker component
import 'vue3-colorpicker/style.css'; // Import the CSS for the ColorPicker

// Define the model property with a string type for the color
const model = defineModel<string>({
    type: String,
    default: '', // Optional default value
});

// Define the props interface for the component to ensure type safety
type PropsType = {
    label?: string;
    defaultColor?: string;
};

defineProps<PropsType>();
</script>

<template>
    <div :key="model" class="flex justify-between flex-wrap items-center w-full flex-1">
        <!-- Label Section -->
        <span v-if="label" class="block font-medium text-xs text-gray-600">{{ label }}</span>
        <!-- ColorPicker Component -->
        <ColorPicker v-model:pure-color="model" format="hex" :debounce="100" shape="square" blur-close />
    </div>
</template>

<style>
div.vc-color-wrap {
    margin-right: 0 !important;
}
</style>
