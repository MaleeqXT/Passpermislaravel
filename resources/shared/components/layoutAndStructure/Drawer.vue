<template>
    <div class="contents">
        <teleport to="body">
            <transition leave-active-class="duration-300">
                <div v-show="show" :class="['relative', zIndex || 'z-900']">
                    <transition name="fade">
                        <div v-if="show" class="fixed inset-0 transform transition-all" @click="$emit('close')">
                            <div class="absolute inset-0 bg-dark/70 z-50" />
                        </div>
                    </transition>
                    <div :class="['fixed inset-y-0 flex w-full', getWidth(), posClass[pos].className]">
                        <transition :name="posClass[pos].trans" mode="out-in">
                            <Scrollable
                                v-show="show"
                                :class="[
                                    'flex flex-col bg-gray-100 shadow-xl w-full max-md:rounded-t-xl',
                                    pos === 'bottom' && 'md:rounded-t-2xl',
                                ]"
                                @scroll:end="$emit('scroll:end')"
                            >
                                <div
                                    v-if="title"
                                    class="px-3 sm:px-4 min-h-12 grid grid-cols-4 items-center sticky top-0 backdrop-blur-md z-10"
                                >
                                    <Button :disabled="!closeable" link @click="$emit('close')">
                                        <span class="absolute -inset-2.5" />
                                        {{ back || 'Fermer' }}
                                    </Button>
                                    <h3 class="text-md/4 font-semibold text-gray-900 col-span-2 text-center">
                                        {{ title }}
                                    </h3>

                                    <div class="flex gap-2 justify-end">
                                        <slot name="actions" />
                                    </div>
                                </div>
                                <div class="flex-1 flex flex-col">
                                    <slot />
                                </div>
                            </Scrollable>
                        </transition>
                    </div>
                </div>
            </transition>
        </teleport>
    </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, watch, getCurrentInstance } from 'vue';
import { Button } from '../actions';
import Scrollable from '../overleys/Scrollable.vue';

// Define props
type PropsType = {
    pos?: 'right' | 'bottom';
    title?: string;
    show: boolean;
    back?: string;
    zIndex?: string;
    id?: string;
    width?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | '5xl' | '6xl' | '7xl' | 'full';
    closeable?: boolean;
    onSave?: (v: any) => void;
};

const props = withDefaults(defineProps<PropsType>(), {
    pos: 'right',
    width: 'md',
    closeable: true,
});

// Define emits
const emit = defineEmits<{
    (event: 'close'): void;
    (event: 'save'): void;
    (event: 'scroll:end'): void;
}>();

// Define positional classes
const posClass: Record<NonNullable<PropsType['pos']>, { trans: string; className: string }> = {
    right: {
        trans: 'drawer',
        className: 'max-md:bottom-0 md:right-0 md:pl-10 max-md:pt-[54px]',
    },
    bottom: {
        trans: 'slide-up',
        className: 'left-0 pt-12 md:pt-14 right-0',
    },
};

// Utility function to determine width classes
const getWidth = (): string => {
    switch (props.width) {
        case 'xs':
            return 'md:max-w-xs';
        case 'sm':
            return 'md:max-w-sm';
        case 'md':
            return 'md:max-w-md';
        case 'lg':
            return 'md:max-w-lg';
        case 'xl':
            return 'md:max-w-xl';
        case '2xl':
            return 'md:max-w-2xl';
        case '3xl':
            return 'md:max-w-3xl';
        case '4xl':
            return 'md:max-w-4xl';
        case '5xl':
            return 'md:max-w-5xl';
        case '6xl':
            return 'md:max-w-6xl';
        case '7xl':
            return 'md:max-w-7xl';
        case 'full':
            return 'max-w-full';
        default:
            return 'md:max-w-md';
    }
};

// Close function
const close = (): void => {
    if (props.closeable) {
        emit('close');
    }
};

// Close on escape key
const closeOnEscape = (e: KeyboardEvent): void => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

// Watch and lifecycle hooks
onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.classList.remove('overflow-hidden');
});

watch(
    () => props.show,
    () => {
        if (props.show) {
            document.body.classList.add('overflow-hidden');
        } else {
            document.body.classList.remove('overflow-hidden');
        }
    }
);
</script>
