<template>
    <div class="mt-1">
        <label v-if="label" class="flex form-label peer-focus:text-blue-500 truncate">
            <span class="w-full">{{ label }}</span>

            <b> {{ modelValueInternal }}/{{ max }} </b>
        </label>
        <div class="relative w-full">
            <input
                type="range"
                :name="name"
                v-model="modelValueInternal"
                :min="min"
                :max="max"
                class="range-control"
                :style="{
                    background: `linear-gradient(to right, #15803d 0%, #1db488 calc(${modelValueInternal} * ${max}%), #e5e7eb calc(${modelValueInternal} * ${max}%) 100%)`,
                    '--value': modelValueInternal.toString(),
                }"
            />
            <div class="flex justify-between text-xs text-gray-500 mb-0">
                <span>{{ min }}</span>
                <span>{{ Math.floor((min + max) / 2) }}</span>
                <span>{{ max }}</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue: number;
    label?: string;
    name?: string;
    min?: number;
    max?: number;
}

const props = withDefaults(defineProps<Props>(), {
    min: 0,
    max: 1,
});
const emit = defineEmits<{
    (e: 'update:modelValue', value: number): void;
}>();

// Computed modelValue binding
const modelValueInternal = computed<number>({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', Number(val)),
});
</script>
