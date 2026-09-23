<script setup lang="ts">
import { ref } from 'vue';
import { Errors } from '../feedbackIndicator';
import { CheckIcon } from '@adersolutions/icons';

interface ICheckboxItem {
    [key: string]: any;
    disabled?: boolean;
    content?: string;
    name?: string;
    id?: string;
}

interface ICheckboxGroupProps {
    label?: string;
    items: ICheckboxItem[];
    keys?: string[];
    id?: string;
    error?: string | string[];
    disabled?: boolean;
    wrapperClass?: string;
    itemClass?: string;
    labelClass?: string;
    extra?: (v: T) => string;
    info?: string;
}

// Using defineModel for two-way binding for update:modelValue
const model = defineModel<any[] | any>({
    default: () => [],
});
if (!model.value) model.value = [];
const props = withDefaults(defineProps<ICheckboxGroupProps>(), {
    keys: () => ['name', 'id'],
    id: Math.random().toString(36).substring(7),
});

// Define custom emits
const customEmits = defineEmits(['click', 'change', 'change:full']);

// Handle change events
const onChange = (item: ICheckboxItem) => {
    const index = (model.value || []).findIndex((v) => v[props.keys[1]] === item[props.keys[1]]);
    if (index === -1) {
        model.value.push(item);
    } else {
        model.value.splice(index, 1);
    }
    customEmits('change', model.value);
    customEmits('change:full', model.value);
    customEmits('click'); // Custom click event
};
const isPresent = (item: ICheckboxItem) => model.value?.some((v) => v[props.keys[1]] === item[props.keys[1]]);
</script>

<template>
    <div :class="['space-y-1', wrapperClass]">
        <label
            v-if="label"
            :for="id"
            :class="['block font-medium text-xs mb-0.5', error && '!text-red-600', labelClass || 'text-gray-600']"
        >
            {{ label }}
        </label>

        <ul :class="['relative grid gap-1']">
            <!-- Loop through items and generate checkboxes -->
            <li
                v-for="(item, idx) in items"
                :key="idx"
                :class="[
                    itemClass || '',
                    item.disabled ? 'pointer-events-none opacity-45' : 'cursor-pointer',
                    'item',
                    isPresent(item) ? 'before:bg-primary shadow-box bg-white border-gray-200' : 'before:bg-transparent border-transparent',
                ]"
                @click="onChange(item)"
            >
                <div class="flex-1">
                    <p class="font-medium">{{ item[keys[0]] }}</p>
                    <span v-if="item.content || extra" class="text-gray-500 text-sm -mt-0.5 block">
                        {{ item.content || extra?.(item) }}
                    </span>
                </div>
                <CheckIcon v-if="isPresent(item)" class="shrink-0 text-primary w-6" />
                <small v-if="item.disabled">Indisponible</small>
            </li>
        </ul>
        <p v-if="info" class="text-sm">{{ info }}</p>
        <Errors v-if="error" :errors="error" />
    </div>
</template>

<style lang="scss" scoped>
.item {
    @apply flex w-full border-t border-l gap-2 items-center rounded-lg hover:bg-white/70 hover:before:bg-gray-400 py-1.5 px-3 relative before:w-1 before:inset-y-2 before:absolute before:left-1 before:rounded-lg;
    &:not(:last-child):after {
        content: '';
        @apply block w-full h-px bg-gray-200 absolute inset-x-0 -bottom-[3px];
    }
}
</style>
