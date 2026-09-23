<script setup lang="ts">
import { MenuHorizontalIcon } from '@adersolutions/icons';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { Head } from '@inertiajs/vue3';
import { Button, Alerts } from '@shared/components';
import { useApp } from '@shared/stores';
import { computed, onBeforeUnmount, onMounted, useSlots } from 'vue';
import Back from './Back.vue';
type PagePropsType = {
    title?: string;
    subtitle?: string;
    back?: boolean | string;
    actions?: { label: string; onAction?: () => void; primary?: boolean; variant?: string }[];
    width?: 'full' | 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl';
    padding?: 'none' | 'xs' | 'sm' | 'md' | 'lg' | '2xl';
    badges?: any[];
};

const props = withDefaults(defineProps<PagePropsType>(), {
    title: '',
    padding: '2xl',
});

const app = useApp();
const slots = useSlots();
const getActions = computed(() => props.actions || app.actions);

const widthClass = () => {
    switch (props.width) {
        case 'full':
            return 'max-w-none';
        case 'xs':
            return 'max-w-screen-xs';
        case 'sm':
            return 'max-w-screen-sm';
        case 'md':
            return 'max-w-screen-md';
        case 'lg':
            return 'max-w-screen-lg';
        case 'xl':
            return 'max-w-screen-xl';
        default:
            return 'max-w-screen-2xl';
    }
};
const paddingClass = () => {
    switch (props.padding) {
        case 'none':
            return '';
        case 'xs':
            return 'px-1 sm:px-2 lg:px-3';
        case 'sm':
            return 'px-2 sm:px-3 lg:px-4';
        case 'md':
            return 'px-4 sm:px-6 lg:px-8';
        case 'lg':
            return 'px-6 sm:px-8 lg:px-12';
        default:
            return 'px-4 sm:px-6 lg:px-8';
    }
};

onMounted(() => {
    app.settings.width = widthClass();
    app.settings.padding = paddingClass();
});

onBeforeUnmount(() => {
    app.actions = [];
});
</script>

<template>
    <main :class="[' flex-1 t-5 flex flex-col  mx-auto text-dark w-full  z-10 relative']">
        <Head :title="title" />
        <div
            :class="[
                'flex sm:items-center max-sm:flex-col lg:justify-between text-dark pb-0 mx-auto w-full',
                app.settings.padding,
                app.settings.width,
            ]"
        >
            <Back v-if="title" class="flex-1" :title="title" :back="back" :badges="badges" />
            <div class="flex-1">
                <slot name="center"></slot>
            </div>
            <ul class="flex-1 flex gap-2 items-center justify-end">
                <template v-if="getActions.length || slots.actions">
                    <slot name="actions"></slot>
                    <li
                        v-for="(action, index) in getActions"
                        :key="index"
                        :class="[action.variant === 'primary' ? 'lg:order-4' : 'hidden md:block lg:order-1']"
                    >
                        <Button :key="index" v-bind="action" secondary @click="action.onAction?.()">
                            {{ action.label }}
                        </Button>
                    </li>

                    <!-- Dropdown -->
                    <Menu v-if="getActions.length - 1 > 0" as="li" class="relative ml-3 lg:hidden">
                        <MenuButton class="btn flex-center px-1 w-9 h-9">
                            <MenuHorizontalIcon class="h-5 w-5" aria-hidden="true" />
                        </MenuButton>

                        <transition name="fade-up">
                            <MenuItems
                                as="ul"
                                class="absolute right-0 z-10 -mr-1 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-dark ring-opacity-5 focus:outline-none"
                            >
                                <MenuItem
                                    v-for="(action, idx) in getActions"
                                    :key="idx"
                                    as="li"
                                    :class="[action.variant === 'primary' && 'hidden']"
                                >
                                    <Button :key="idx" v-bind="action" link mono>
                                        {{ action.label }}
                                    </Button>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                </template>
            </ul>
        </div>
        <div :class="['flex-1 w-full mx-auto flex', app.settings.padding ? app.settings.padding + ' pb-10' : '', app.settings.width]">
            <slot name="sidebar" />
            <div :class="['flex-1 flex flex-col ', useSlots().sidebar && 'v-rainbow relative']">
                <Alerts v-if="!(padding === 'none' && width === 'full')" :class="!paddingClass() && 'pt-5 !mb-0 px-5'" />
                <!-- <teleport to=".alert-slot" defer :disabled="">
                </teleport> -->
                <slot />
            </div>
        </div>
    </main>
</template>
