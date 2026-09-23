<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { createPopper, Placement } from '@popperjs/core';

const emit = defineEmits(['popover-opened']);

interface Ipopup {
    position?: Placement;
    disabled?: boolean;
    dark?: boolean;
    hoverable?: boolean;
    pa?: number;
    offset?: number;
    delay?: number;
    arrow?: boolean;
}

const props = defineProps<Ipopup>();
// State variables
const isPopperVisible = ref(false);
const popperElement = ref<HTMLDivElement | null>(null);
const referenceElement = ref<HTMLDivElement | null>(null);
const arrowElement = ref(null); // Arrow reference
let popperInstance: ReturnType<typeof createPopper> | null = null;

// Function to initialize Popper.js
const initializePopper = async () => {
    await nextTick(); // Wait for the DOM to update

    if (referenceElement.value && popperElement.value) {
        const arrowOption =
            props.arrow && arrowElement.value
                ? [
                      {
                          name: 'arrow',
                          options: {
                              element: arrowElement.value,
                              padding: props.pa || 8, // Adjust padding between the arrow and the tooltip edge
                          },
                      },
                  ]
                : [];
        popperInstance = createPopper(referenceElement.value, popperElement.value, {
            placement: (props.position as Placement) || 'bottom', // Adjust placement as needed
            modifiers: [
                ...arrowOption,
                {
                    name: 'offset',
                    options: {
                        offset: [0, props.offset || 4], // Adjust the tooltip position relative to the reference element
                    },
                },
                {
                    name: 'preventOverflow',
                    options: {
                        boundary: 'viewport',
                    },
                },
                {
                    name: 'flip',
                    options: {
                        fallbackPlacements: ['top', 'right', 'left'],
                    },
                },
            ],
        });
    }
};

let timeout: ReturnType<typeof setTimeout> | undefined;

// Function to show the popper
const showPopper = () => {
    if (props.disabled) {
        return;
    }
    timeout = setTimeout(() => {
        isPopperVisible.value = true;
        emit('popover-opened');
        initializePopper();
    }, props.delay ?? 0);
};

// Function to hide the popper
const hidePopper = () => {
    clearTimeout(timeout);
    if (props.hoverable) {
        const getHoverd = document.querySelector('.popper-content:hover');
        if (popperElement.value?.contains(getHoverd)) {
            return;
        }
    }
    isPopperVisible.value = false;
};

// Function to toggle popper visibility
const togglePopper = (e) => {
    handleClickOutside(e);
    if (isPopperVisible.value) {
        hidePopper();
    } else {
        showPopper();
    }
};
// Event handler for clicks outside the tooltip
const handleClickOutside = (event: MouseEvent) => {
    if (
        isPopperVisible.value &&
        popperElement.value &&
        referenceElement.value &&
        !popperElement.value.contains?.(event.target as Node) &&
        !referenceElement.value.contains?.(event.target as Node)
    ) {
        hidePopper();
    }
};

// Setup click outside listener on mount
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

// Cleanup on unmount
onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);

    if (popperInstance) {
        popperInstance.destroy();
        popperInstance = null;
    }
});

const popperClasses = 'absolute z-50 mt-2 w-48 shadow-lg';
</script>
<template>
    <div :class="['contents']">
        <div
            ref="referenceElement"
            :class="[
                $attrs.class,
                'cursor-pointer t-3 inline-block relative before:z-0 before:absolute before:-inset-2',
                disabled ? 'pointer-events-none' : ' active:opacity-70',
            ]"
            @mouseenter="hoverable && showPopper()"
            @mouseleave="hoverable && hidePopper()"
            @click.prevent="togglePopper"
        >
            <slot />
        </div>
        <!-- Use Teleport to render outside parent container -->
        <Teleport to="body">
            <div
                v-if="isPopperVisible"
                ref="popperElement"
                :class="[
                    'popper-content rounded-xl border z-900 min-h-8 min-w-fit h-fit ring-gray-100 grid',
                    popperClasses,
                    position === 'bottom' ? 'animate-up' : 'animate-down',
                    dark ? 'popper-dark' : 'popper-light',
                ]"
                :style="{ width: `${referenceElement?.offsetWidth || 200}px` }"
                @mouseleave="hoverable && hidePopper()"
                @mouseenter="hoverable && showPopper()"
            >
                <slot name="content" :close="hidePopper" />
                <div v-if="arrow" ref="arrowElement" class="popper-arrow"></div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped lang="scss">
.popper {
    &-light {
        --border-color: #d1d5db;
        @apply bg-white text-dark border-gray-300;
        .popper-arrow:before {
            @apply bg-white;
        }
    }
    &-dark {
        --border-color: #000000;
        @apply bg-dark text-white border-white/30;
        .popper-arrow:before {
            @apply bg-dark;
        }
    }
}

.popper-arrow {
    position: absolute;
    z-index: -1;
}
.popper-arrow::before {
    content: '';
    @apply w-[10px] h-[10px] block border border-transparent transform rotate-45 z-1;
}

[data-popper-placement^='top'] .popper-arrow {
    bottom: -5px; /* Position the arrow at the top edge */
    &::before {
        border-bottom-right-radius: 4px;
        border-color: transparent var(--border-color) var(--border-color) transparent;
    }
}

[data-popper-placement^='right'] .popper-arrow {
    left: -5px;

    &::before {
        border-bottom-left-radius: 4px;
        border-color: transparent transparent var(--border-color) var(--border-color);
    }
}

[data-popper-placement^='bottom'] .popper-arrow {
    top: -5px;
    &::before {
        border-top-left-radius: 4px;
        border-color: var(--border-color) transparent transparent var(--border-color);
    }
}

[data-popper-placement^='left'] .popper-arrow {
    right: -5px;
    &::before {
        border-bottom-right-radius: 4px;
        border-color: var(--border-color) var(--border-color) transparent transparent;
    }
}
</style>
