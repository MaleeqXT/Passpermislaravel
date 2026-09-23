import { onMounted, onUnmounted, ref } from 'vue';
import { useDebounce } from './useDebounce';

interface StickyParams {
    blocked: boolean;
    immediate: boolean;
}

export const useSticky = (top: number = 100, params: StickyParams = { blocked: false, immediate: false }) => {
    // Use debounce with immediate option handling
    const debounce = useDebounce(params.immediate ? 0 : 20);

    // Reactive references for tracking sticky state and scroll position
    const isSticky = ref<boolean>(false);
    const scrollPosition = ref<number>(0);

    // Function to handle the scroll event and determine sticky state
    const onScroll = (): void => {
        scrollPosition.value = window.scrollY || document.documentElement.scrollTop;
        isSticky.value = scrollPosition.value > top;
    };

    // Debounced scroll handler
    const handleScroll = debounce(onScroll);

    // Set up event listener on mount, and clean up on unmount
    onMounted(() => {
        if (params.blocked) {
            return;
        }
        handleScroll();
        window.addEventListener('scroll', handleScroll);
    });

    onUnmounted(() => {
        window.removeEventListener('scroll', handleScroll);
    });

    return [isSticky, handleScroll] as const;
};
