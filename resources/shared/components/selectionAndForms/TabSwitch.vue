<script setup lang="ts">
import { useRoute } from '@shared/hooks';
import { computed } from 'vue';
import type { GlobalObjType } from '@common/types';

// Define the interface for the component props
interface PropsType {
    modelValue?: string | number | boolean | undefined | null;
    items: GlobalObjType<any>[];
    label?: string;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    color?: string;
    disabled?: boolean;
    full?: boolean;
    light?: boolean;
    short?: boolean;
    param?: string;
}

// Define props using the interface
const props = defineProps<PropsType>();

// Emit events for model value change and custom change events
const emit = defineEmits(['update:model-value', 'change']);

// Use route for tracking params
const params = useRoute();

// Method to check if an item is active
const isActive = (item: GlobalObjType<any>) => {
    const mv = props.modelValue ?? (props.param ? params[props.param] : '') ?? '';
    return mv == item.id;
};

// Method to get the active item
const getActiveItem = () => {
    return props.items?.find(isActive);
};

// Computed to find the index of the active item
const indexOfActive = computed(() => props.items?.findIndex(isActive));

// Method to determine the size class based on the prop
const getStyleSize = () => {
    switch (props.size) {
        case 'xs':
            return 'h-6 text-xs px-2';
        case 'sm':
            return 'h-7 text-xs px-2';
        case 'lg':
            return 'h-10 text-md px-4';

        case 'xl':
            return 'h-10 md:h-12 md:text-lg px-2 md:px-4';
        default:
            return 'h-8 text-sm px-2';
    }
};

// Method to handle value changes and emit the new value
const onChange = (item: GlobalObjType<any>) => {
    const { id } = item;
    emit('change', id);
    emit('update:model-value', id);
    if (props.param) {
        params.set({ [props.param]: id ?? null });
    }
};
</script>

<template>
    <div :class="['p-0.5 rounded-lg h-fit', full ? 'w-full' : 'w-fit', light ? '' : 'bg-dark text-white']">
        <ul
            class="grid items-center flex-wrap relative w-full tab"
            :style="{
                gridTemplateColumns: `repeat(${items.length}, 1fr)`,
            }"
        >
            <!-- Active item indicator -->
            <li
                aria-hidden="true"
                :style="{
                    transform: `translateX(${indexOfActive * 100}%)`,
                    width: 100 / items.length + '%',
                }"
                :class="[
                    'absolute inset-0 pointer-events-none inline-block rounded-[7px]  t-3',
                    disabled ? '' : 'shadow-inner ',
                    indexOfActive === -1 && 'opacity-0',
                    getActiveItem()?.class ? ' tab-' + getActiveItem()?.class : 'bg-gradient-to-tl from-gray-300 to-gray-200 ',
                ]"
            />

            <!-- List of items -->
            <li
                v-for="(item, key) in items"
                :key="key"
                :class="[
                    'flex-center flex-1 btn-hover !outline-none relative t-2 flex-col leading-4',
                    getStyleSize(),
                    disabled && 'opacity-70 cursor-not-allowed pointer-events-none select-none',
                    isActive(item) ? (!item.class ? 'text-dark' : 'text-white') : '',
                ]"
                :tooltip="short ? item.name : undefined"
                @click="onChange(item)"
            >
                <span>{{ short ? item.sn : item.name }}</span>
                <b v-if="item.content" class="text-2xs opacity-70">{{ item.content }}</b>
            </li>
        </ul>
    </div>
</template>
