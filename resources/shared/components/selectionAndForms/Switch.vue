<script setup lang="ts">
// Define the interface for the component props
interface SwitchProps {
    label?: string; // Optional label for the switch
    labelClass?: string; // Additional classes for the label
    disabled?: boolean; // Whether the switch is disabled
}

// Use defineProps to bind the interface to the component props
defineProps<SwitchProps>();

// Define model for two-way binding
const modelValue = defineModel<boolean>('modelValue', {
    required: true,
    default: false,
});
</script>

<template>
    <!-- Container for the switch and label -->
    <div :class="['flex justify-between gap-x-4 items-center flex-wrap', disabled && 'opacity-50 cursor-not-allowed']">
        <!-- Display label if provided -->
        <label v-if="label" :class="['block font-medium text-xs text-gray-600', labelClass]">
            {{ label }}
        </label>

        <!-- Custom switch component -->
        <div
            :class="[
                'relative inline-flex items-center h-2 w-12 cursor-pointer rounded-full transition-colors duration-200',
                modelValue ? 'bg-primary' : 'bg-gray-400',
                disabled && 'pointer-events-none',
            ]"
            role="switch"
            :aria-checked="modelValue"
            @click="!disabled && (modelValue = !modelValue)"
        >
            <!-- Toggle indicator -->
            <span :class="['absolute h-5 w-5 bg-dark  rounded-full  t-3', modelValue ? 'translate-x-7' : 'translate-x-0']" />
        </div>
    </div>
</template>
