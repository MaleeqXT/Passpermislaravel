<template>
    <div class="contents">
        <teleport to="#side-drawer">
            <transition
                enter-active-class="t-4"
                enter-from-class="transform translate-x-full absolute inset-y-0 right-0"
                enter-to-class="transform translate-x-0 relative"
                leave-active-class="t-4"
                leave-from-class="transform translate-x-0 relative"
                leave-to-class="transform translate-x-full absolute inset-y-0 right-0"
            >
                <div v-show="show" class="size-full min-w-[22rem] t-4 bg-gray-100 shadow-box rounded-t-xl flex flex-col">
                    <div class="text-dark flex justify-between items-center px-3 py-2">
                        <h3 class="text-xl sm:text-lg font-semibold">
                            {{ title }}
                        </h3>
                        <button type="button" class="btn-m hover:bg-gray-200 p-1 rounded-lg" @click="close">
                            <XIcon class="h-4 w-4 fill-current" aria-hidden="true" />
                        </button>
                    </div>
                    <div class="flex-1 flex flex-col">
                        <slot />
                    </div>
                </div>
            </transition>
        </teleport>
    </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import { XIcon } from '@adersolutions/icons';
const emit = defineEmits(['close']);
const props = defineProps({
    title: String,
    show: Boolean,
    zIndex: String,
    id: String,
    width: String,
});

const close = () => emit('close');

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(close);
</script>
