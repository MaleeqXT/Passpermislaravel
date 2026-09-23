<script setup lang="ts">
import { defineProps, ref, PropType } from 'vue';

// Define the type for the options array
interface Tab {
    name: string;
    id: string | boolean | number;
}
const emit = defineEmits(['change']);
const model = defineModel<number | string | boolean>({
    type: [Number, String, Boolean] as PropType<Tab['id']>,
    default: '',
});
withDefaults(defineProps<{ options: Tab[] }>(), {
    options: () => [],
});

// Method to select the tab
function onChange(tab: Tab) {
    model.value = tab.id;
    emit('change', tab.id);
}
</script>
<template>
    <ul :class="['w-fit grid p-0.5 !divide-none filter-control', 'grid-cols-' + options.length]">
        <li
            v-for="(tab, index) in options"
            :key="index"
            :class="[
                'px-2 flex-center cursor-pointer flex-1 rounded-md !h-auto !py-0 truncate',
                model === tab.id ? 'btn-header  !shadow-inner' : 'hover:bg-white/70',
            ]"
            @click="onChange(tab)"
        >
            {{ tab.name }}
        </li>
    </ul>
</template>
