<script setup lang="ts">
import { ref } from 'vue';
import { Errors } from '../feedbackIndicator';
import { CheckIcon } from '@adersolutions/icons';

interface IRadioItem {
    [key: string]: any;
    disabled?: boolean;
    content?: string;
}

type IRadioGroupProps<T = any> = {
    label?: string;
    items: IRadioItem[];
    keys?: string[];
    id?: string;
    error?: string | string[];
    disabled?: boolean;
    itemClass?: string;
    labelClass?: string;
    info?: string;
    extra?: (v: T) => string;
};

// Using defineModel for two-way binding for update:modelValue
const model = defineModel<string | number | null>();
const modelFull = defineModel<IRadioItem | null>('full', {
    default: () => ({}),
});

const props = withDefaults(defineProps<IRadioGroupProps>(), {
    keys: () => ['name', 'id'],
    id: Math.random().toString(36).substring(7),
});

// Local ref to hold selected value

const uid = ref(Math.random().toString(36).substring(7));

// Define custom emits
const emit = defineEmits(['click', 'change', 'change:full']);

// Handle change events
const onChange = (item: IRadioItem) => {
    const res = model.value === item[props.keys[1]] ? null : item;
    model.value = res?.[props.keys[1]];
    modelFull.value = res;
    emit('change', model.value);
    emit('change:full', res);
    emit('click'); // Custom click event
};
const isActive = (item: IRadioItem) => {
    const k = props.keys[1];
    return modelFull.value?.[k] === item[k] || model.value === item[k];
};
</script>

<template>
    <div class="space-y-1">
        <label
            v-if="label"
            :for="id"
            :class="['block font-medium text-xs mb-0.5 ', error && '!text-red-600', labelClass || 'text-gray-600']"
        >
            {{ label }}
        </label>

        <ul :class="[' grid gap-1 bg-white shadow-sm rounded-xl ']">
            <!-- Loop through items and generate radio buttons -->
            <li
                v-for="(item, idx) in items"
                :key="idx"
                :class="[
                    itemClass || '',
                    item.disabled ? 'pointer-events-none opacity-45' : 'cursor-pointer',
                    'item',
                    isActive(item) ? 'before:bg-primary bg-white ' : 'before:bg-transparent',
                ]"
                @click="onChange(item)"
            >
                <div class="flex-1">
                    <p class="font-medium">{{ item[keys[0]] }}</p>
                    <slot name="content" :item="item">
                        <span v-if="item.content || extra" class="text-gray-500 text-sm -mt-0.5 block">
                            {{ item.content || extra?.(item) }}
                        </span>
                    </slot>
                </div>
                <CheckIcon v-if="isActive(item)" class="shrink-0 text-primary w-6 -m-1" />
                <small v-if="item.disabled">Indisponible</small>
            </li>
        </ul>
        <p v-if="info" class="text-sm">{{ info }}</p>
        <Errors v-if="error" :errors="error" />
    </div>
</template>
<style lang="scss" scoped>
.item {
    @apply flex w-full  active:opacity-70 gap-2 items-center min-h-8 rounded-lg hover:bg-white/70 hover:before:bg-gray-400 py-1.5 px-3 relative before:w-1 before:inset-y-2 before:absolute  before:left-1 before:rounded-lg;
    &:not(:last-child):after {
        content: '';
        @apply block w-full h-px bg-gray-200 absolute inset-x-0 -bottom-[3px];
    }
}
</style>
