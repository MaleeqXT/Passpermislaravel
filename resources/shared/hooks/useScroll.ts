import { reactive, onMounted, onUnmounted, computed } from 'vue';

/**
 * Throttle function to limit the rate at which a function can fire.
 *
 * @param func - The function to throttle.
 * @param delay - The number of milliseconds to wait before allowing the function to be called again.
 * @returns A throttled version of the input function.
 */
function throttle(func: (...args: any[]) => void, delay: number) {
    let lastCall = 0;
    return function (...args: any[]) {
        const now = Date.now();
        if (now - lastCall >= delay) {
            lastCall = now;
            return func(...args);
        }
    };
}

export function useScroll(target: HTMLElement | null = null) {
    const state = reactive({
        scrollX: 0,
        scrollY: 0,
        isHeaderVisible: true,
        blurIntensity: 0,
    });

    let lastScrollY = 0; // Track the last scroll position for scroll direction detection
    let isScrollingDown = false; // Flag to track scrolling direction

    // A function to update the scroll position
    const updateScroll = throttle(() => {
        const scrollTarget = target || window; // Default to window if no target is provided
        const currentY = scrollTarget instanceof Window ? window.scrollY : scrollTarget.scrollTop;

        // Update scrollY accurately
        state.scrollY = currentY;
        // Detect scroll direction and update header visibility
        if (currentY > lastScrollY && currentY > 20) {
            state.isHeaderVisible = false; // Hide header when scrolling down
            isScrollingDown = true; // Set the flag to true when scrolling down
        } else if (currentY <= 20 || currentY < lastScrollY) {
            state.isHeaderVisible = true; // Show header when scrolling up or near top
            isScrollingDown = false; // Set the flag to false when scrolling up
        }

        // Apply blur effect only when scrolling down
        if (isScrollingDown && currentY > 0) {
            state.blurIntensity = Math.min(currentY / 100, 1); // Apply blur, capped at 1
        } else {
            state.blurIntensity = 0; // Reset blur when scrolling up or at top
        }

        lastScrollY = currentY; // Update last scroll position
    }, 10); // Throttling scroll event to fire at most once every 100ms

    // Computed property for dynamic styles (blur effect)
    const blurStyle = computed(() => ({
        filter: `blur(${state.blurIntensity * 5}px)`, // Apply blur based on scroll intensity
    }));

    // Attach the scroll event when mounted
    onMounted(() => {
        const scrollTarget = target || window;
        scrollTarget.addEventListener('scroll', updateScroll);
        updateScroll(); // Initialize scroll positions
    });

    // Clean up when component is unmounted
    onUnmounted(() => {
        const scrollTarget = target || window;
        scrollTarget.removeEventListener('scroll', updateScroll);
    });

    return {
        x: computed(() => state.scrollX),
        y: computed(() => state.scrollY),
        show: computed(() => state.isHeaderVisible),
        blurStyle, // Expose computed blur style
    };
}
