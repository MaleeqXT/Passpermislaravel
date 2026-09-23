<script setup lang="ts">
import { ChevronRightIcon } from '@adersolutions/icons';
import { Link } from '@inertiajs/vue3';
import { isActive } from '@shared/utils';
import { reactive } from 'vue';

const props = defineProps({
    navItems: Object,
    active: String,
    title: String,
});

const state = reactive({
    active: props.navItems[0]?.pathname || '',
});

const handleClick = (item) => {
    item.onAction?.();
    state.active = item.pathname;
};

const isItemActive = (item) => {
    if (!item.path) {
        return state.active === item.pathname;
    }
    return isActive('', item.pathname);
};
</script>

<template>
    <ul class="flex flex-col divide-y border-x border-gray-300 bg- flex-1 max-w-72 sticky top-0 h-dvh rounded-tl-xl">
        <li>
            <h3 class="text-sm font-bold text-dark/80 p-3">{{ title || 'Navigation' }}</h3>
        </li>
        <slot name="top" />
        <li v-for="(item, index) in navItems" :key="index">
            <component
                :is="item.path ? Link : 'button'"
                :href="item.path"
                :class="[
                    isItemActive(item)
                        ? 'bg-white text-primary btn-m font-bold shadow-sm bg-rainbow rainbow-opacity-30 '
                        : 'hover:opacity-70 hover:bg-white/70',
                    'flex gap-x-3  p-3 t-3',
                ]"
                @click="handleClick(item)"
            >
                <component :is="item.icon" class="w-5 self-start" />
                <div class="flex-grow text-sm">
                    {{ item.title }}
                    <p v-if="item.subtitle" class="text-2xs font-normal mt-0.5 text-dark/70">{{ item.subtitle }}</p>
                </div>
                <ChevronRightIcon class="h-4 w-4 text-dark/20" aria-hidden="true" />
            </component>
        </li>

        <slot />
    </ul>
</template>
