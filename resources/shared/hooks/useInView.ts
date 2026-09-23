import { ref, Ref, watchEffect, onUnmounted } from 'vue';

export function useInView(element: Ref<HTMLElement | null>) {
    const inView = ref(false);

    const observer = new IntersectionObserver(
        ([entry]) => {
            inView.value = entry.isIntersecting;
        },
        {
            threshold: 1,
            rootMargin: '10px',
        }
    );

    watchEffect((onCleanup) => {
        const el = element.value;

        if (el) {
            observer.observe(el);
        }

        onCleanup(() => {
            if (el) {
                observer.unobserve(el);
            }
        });
    });

    onUnmounted(() => {
        observer.disconnect();
    });

    return inView;
}
