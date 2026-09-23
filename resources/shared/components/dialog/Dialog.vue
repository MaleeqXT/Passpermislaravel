<script setup lang="ts">
import { computed, onMounted, onUnmounted, watch } from 'vue';
import { XIcon } from '@adersolutions/icons';
// import { Alerts } from '../feedbackIndicator';

// Define Props Interface
interface IModalProps {
    title?: string;
    zIndex?: string;
    customClass?: string;
    className?: string;
    show: boolean;
    maxWidth?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl';
    closeable?: boolean;
    scrollable?: boolean;
    backdrop?: boolean;
    mobile?: boolean;
}

// Define Props withDefaults
const props = withDefaults(defineProps<IModalProps>(), {
    maxWidth: '2xl',
    closeable: true,
    scrollable: true,
    backdrop: true,
});

const emit = defineEmits(['close']);

// Watch for show prop changes
watch(
    () => props.show,
    () => {
        if (props.show) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
);

// Close the modal
const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

// Close modal on Escape key press
const closeOnEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

// Add and remove Escape key listener
onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

// Computed class for maxWidth
const maxWidthClass = computed(() => {
    return (
        {
            xs: 'md:max-w-sm h-fit',
            sm: 'md:max-w-screen-sm h-fit',
            md: 'md:max-w-screen-md h-fit',
            lg: 'md:max-w-screen-lg h-full',
            xl: 'md:max-w-screen-xl h-full',
            '2xl': 'md:max-w-2xl h-full',
        }[props.maxWidth] || 'md:max-w-2xl h-fit'
    );
});
</script>

<template>
    <div class="contents">
        <teleport to="body">
            <transition leave-active-class="duration-200">
                <div
                    v-show="props.show"
                    :class="[
                        'fixed inset-0 flex items-center max-h-screen',
                        props.className || '',
                        props.mobile ? 'p-0' : 'px-4 py-6 lg:px-0',
                        props.zIndex || 'z-[899]',
                    ]"
                    scroll-region
                >
                    <transition name="fade">
                        <div
                            v-show="props.show && props.backdrop"
                            class="fixed inset-0 transform transition-all bg-dark/50 backdrop-blur-md"
                            @click="close"
                        />
                    </transition>

                    <transition name="slide-up">
                        <div
                            v-show="props.show"
                            :class="[
                                maxWidthClass,
                                props.scrollable && 'overflow-y-auto',
                                props.mobile ? 'modal-mobile h-fit' : '',
                                props.customClass ||
                                    'rounded-xl shadow-box bg-white transform t-2 w-full md:mx-auto max-h-[calc(100vh-5rem)] flex flex-col',
                            ]"
                        >
                            <div
                                v-if="props.title"
                                class="z-1 border-b border-gray-300 bg-gray-200 rounded-t-xl h-fit sticky top-0 text-md font-semibold px-2 py-3 md:p-4"
                            >
                                {{ props.title }}
                                <button v-if="props.closeable" @click="close">
                                    <XIcon class="absolute top-1/2 -translate-y-1/2 right-3 h-6 w-6 hover:text-red-500" />
                                </button>
                            </div>
                            <div class="flex-1 flex flex-col">
                                <!-- <Alerts dialog /> -->
                                <slot />
                            </div>
                            <div
                                v-if="$slots.footer"
                                class="z-1 border-t border-gray-300 bg-gray-100 md:rounded-b-xl h-fit sticky bottom-0 text-md font-semibold p-2 md:px-4"
                            >
                                <slot name="footer" :close="close" />
                            </div>
                        </div>
                    </transition>
                </div>
            </transition>
        </teleport>
    </div>
</template>
