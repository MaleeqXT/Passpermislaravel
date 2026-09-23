<template>
    <div
        class="relative select-none w-full"
        :class="isVertical ? 'h-[80vh]' : 'h-64'"
        ref="containerRef"
        @click="isPaused = true"
        @mousedown="startDrag"
        @touchstart.prevent="startDrag"
        @mouseup="stopDrag"
        @mouseleave="handleMouseLeave"
        @touchend="stopDrag"
        @mousemove="onDrag"
        @touchmove.prevent="onDrag"
    >
        <div
            class="absolute flex gap-4"
            :class="isVertical ? 'flex-col' : 'flex-row'"
            ref="scrollerRef"
            :style="{ transform: transformStyle }"
        >
            <slot :items="duplicatedReviews">
                <ReviewCard v-for="(review, index) in duplicatedReviews" :key="index" :review="review" />
            </slot>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import ReviewCard from './ReviewCard.vue';

const props = defineProps({
    direction: { type: String, default: 'vertical' }, // 'horizontal' or 'vertical'
    reverse: { type: Boolean, default: false }, // User's original default
    speed: { type: Number, default: 0.5 }, // pixels per animation frame
    reviews: {
        type: Array,
        default: () => [
            { name: 'Alice', text: 'Great service!' },
            { name: 'Bob', text: 'Loved it!' },
            { name: 'Charlie', text: 'Excellent support.' },
            { name: 'Diana', text: 'Highly recommended.' },
        ],
    },
});

const isVertical = computed(() => props.direction === 'vertical');
const animationSpeed = computed(() => props.speed); // Speed is always positive magnitude

const isPaused = ref(false);
const offset = ref(0);

const containerRef = ref(null);
const scrollerRef = ref(null);
let frameId = null;

const duplicatedReviews = computed(() => {
    if (!props.reviews || props.reviews.length === 0) return [];
    // Ensure enough items for very wide containers if necessary, though 2x is standard for this technique
    return [...props.reviews, ...props.reviews];
});

const itemMetrics = ref({ itemSize: 0, itemGap: 0, effectiveSize: 0 });

const calculateItemMetrics = () => {
    if (!scrollerRef.value || !scrollerRef.value.children || scrollerRef.value.children.length === 0) {
        // Only reset to zeros if it was uninitialized, to avoid flicker if children temporarily disappear
        if (itemMetrics.value.effectiveSize === 0) {
            itemMetrics.value = { itemSize: 0, itemGap: 0, effectiveSize: 0 };
        }
        return;
    }

    const children = scrollerRef.value.children;
    const firstChild = children[0];
    const currentItemSize = isVertical.value ? firstChild.offsetHeight : firstChild.offsetWidth;

    let currentItemGap = 0;
    let currentEffectiveSize = currentItemSize; // Default if only one child type or cannot measure gap

    if (children.length >= 2) {
        const secondChild = children[1];
        if (isVertical.value) {
            currentEffectiveSize = secondChild.offsetTop - firstChild.offsetTop;
        } else {
            currentEffectiveSize = secondChild.offsetLeft - firstChild.offsetLeft;
        }
        currentItemGap = currentEffectiveSize - currentItemSize;

        if (currentItemGap < 0) currentItemGap = 0; // Gap should be non-negative
        if (currentEffectiveSize < currentItemSize) currentEffectiveSize = currentItemSize; // Sanity check
    }

    itemMetrics.value = {
        itemSize: currentItemSize,
        itemGap: currentItemGap,
        effectiveSize: currentEffectiveSize,
    };
};

watch(
    [duplicatedReviews, () => props.direction],
    async () => {
        // Wait for DOM to update after review/direction changes
        await nextTick();
        calculateItemMetrics();
        // Optional: Reset offset if content changes drastically, to avoid large jumps
        // offset.value = 0;
    },
    { immediate: true }
); // immediate: true can be tricky with DOM refs, ensure refs are available.

onMounted(async () => {
    await nextTick(); // Ensure DOM elements are rendered
    calculateItemMetrics();
    frameId = requestAnimationFrame(animate);
});

onBeforeUnmount(() => {
    if (frameId) {
        cancelAnimationFrame(frameId);
    }
});

const transformStyle = computed(() => {
    const translate = isVertical.value ? 'translateY' : 'translateX';
    return `${translate}(${offset.value}px)`;
});

const animate = () => {
    if (!scrollerRef.value || !containerRef.value || isPaused.value || isDragging.value) {
        frameId = requestAnimationFrame(animate);
        return;
    }

    const { effectiveSize } = itemMetrics.value;

    if (effectiveSize === 0 || props.reviews.length === 0) {
        frameId = requestAnimationFrame(animate);
        return;
    }

    const scrollDirectionMultiplier = props.reverse ? 1 : -1;
    offset.value += scrollDirectionMultiplier * animationSpeed.value;

    // Normalize offset and scroller items
    if (scrollDirectionMultiplier === -1) {
        // Moving left/up (offset becomes more negative)
        while (offset.value <= -effectiveSize) {
            offset.value += effectiveSize;
            if (scrollerRef.value.children.length > 1) {
                // Need at least 2 children to cycle
                scrollerRef.value.appendChild(scrollerRef.value.children[0]);
            } else {
                break; // Not enough items to cycle
            }
        }
    } else {
        // Moving right/down (offset becomes more positive, scrollDirectionMultiplier === 1)
        while (offset.value > 0) {
            // If offset is positive, we've scrolled "past the beginning"
            offset.value -= effectiveSize;
            if (scrollerRef.value.children.length > 1) {
                scrollerRef.value.prepend(scrollerRef.value.children[scrollerRef.value.children.length - 1]);
            } else {
                break;
            }
        }
    }
    frameId = requestAnimationFrame(animate);
};

/* Drag / Swipe Support */
const isDragging = ref(false);
let dragStartClient = 0;
let dragStartOffset = 0;

const startDrag = (e) => {
    if (isDragging.value) return; // Already dragging

    isDragging.value = true;
    isPaused.value = true; // Pause animation
    if (frameId) {
        // Cancel ongoing animation frame to prevent interference
        cancelAnimationFrame(frameId);
        frameId = null;
    }

    dragStartOffset.value = offset.value;
    dragStartClient = isVertical.value ? e.touches?.[0]?.clientY ?? e.clientY : e.touches?.[0]?.clientX ?? e.clientX;

    // Add listeners to window for mouseup/mousemove to handle dragging outside component
    window.addEventListener('mousemove', onDrag);
    window.addEventListener('touchmove', onDrag, { passive: false }); // passive:false if calling preventDefault
    window.addEventListener('mouseup', stopDrag);
    window.addEventListener('touchend', stopDrag);
};

const onDrag = (e) => {
    if (!isDragging.value) return;
    // For touchmove, e.preventDefault() is already called via @touchmove.prevent
    // For mousemove, if it's listened on window, preventDefault might not be needed unless to stop text selection
    if (e.type === 'touchmove') e.preventDefault();

    const currentClient = isVertical.value ? e.touches?.[0]?.clientY ?? e.clientY : e.touches?.[0]?.clientX ?? e.clientX;

    const delta = currentClient - dragStartClient;
    offset.value = dragStartOffset.value + delta;
};

const stopDrag = () => {
    if (!isDragging.value) return;

    isDragging.value = false;
    // isPaused is handled by mouseenter/mouseleave or set to false to resume
    // Resume animation if not paused by mouseenter
    if (!containerRef.value?.matches(':hover')) {
        // Check if mouse is still over container
        isPaused.value = false;
    }

    // Clean up global listeners
    window.removeEventListener('mousemove', onDrag);
    window.removeEventListener('touchmove', onDrag);
    window.removeEventListener('mouseup', stopDrag);
    window.removeEventListener('touchend', stopDrag);

    if (frameId) cancelAnimationFrame(frameId); // Cancel any potentially pending frame from drag
    frameId = requestAnimationFrame(animate); // Restart animation
};

const handleMouseLeave = () => {
    if (isDragging.value) {
        stopDrag(); // If dragging and mouse leaves, treat as drag end
    }
    isPaused.value = false; // Always unpause on mouse leave, unless dragging logic re-pauses
};
</script>
