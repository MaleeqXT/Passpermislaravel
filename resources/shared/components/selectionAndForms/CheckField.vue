<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Errors } from '../feedbackIndicator';

// Define the props interface to ensure proper type checking
interface Props {
    checked?: boolean | string[]; // Either a boolean or an array for multi-select scenarios
    value?: string | number | boolean | object | null;
    error?: string | string[]; // Can be a string or an array of errors
    label?: string;
    indeterminate?: boolean;
    disabled?: boolean;
    id?: string;
    content?: string;
}

// Define props using the Props interface
const props = defineProps<Props>();

// Emit events for checked updates
const emit = defineEmits<{
    (e: 'update:checked', val: boolean): void;
    (e: 'update', val: boolean): void;
}>();

// Unique ID for each checkbox instance
const uniqueID = ref(Math.random().toString(36).substring(7));

// Computed property for proxyChecked with getter and setter
const proxyChecked = computed({
    get() {
        if (props.disabled) {
            return false;
        }
        return props.checked;
    },

    set(val: boolean) {
        emit('update:checked', val);
        emit('update', val);
    },
});

// Watch the disabled prop and reset checked value when disabled
watch(
    () => props.disabled,
    (val) => {
        if (val) {
            proxyChecked.value = false;
        }
    },
    { immediate: true }
);
</script>

<template>
    <div :class="['w-full flex', content ? 'items-start' : 'items-center', disabled && 'opacity-50']">
        <!-- CheckField input -->
        <input
            :id="id || uniqueID"
            v-model="proxyChecked"
            aria-describedby="check-description"
            type="checkbox"
            :value="value"
            :disabled="disabled"
            :indeterminate="indeterminate"
            class="h-5 w-5 rounded-md over border-gray-300 text-primary focus:ring-primary accent-primary"
        />

        <!-- Label and content section -->
        <div v-if="label || content" class="ml-3 flex-1">
            <label :for="id || uniqueID" class="block font-medium text-sm leading-4 text-gray-900 select-none">
                {{ label }}
            </label>
            <p v-if="content" :id="(id || uniqueID) + '-description'" class="text-2xs text-slate-700 block">
                {{ content }}
            </p>

            <!-- Display errors if any -->
            <Errors v-if="error" :errors="error" />
        </div>
    </div>
</template>
