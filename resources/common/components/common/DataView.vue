<script setup lang="ts">
import { EmptyState, Spinner } from '@shared/components';
import { UseQueryType } from '@shared/hooks';
import { ref, onMounted, watch, nextTick } from 'vue';

type PropsType = {
    query: UseQueryType<unknown, any>;
    empty?: number;
};

const emit = defineEmits(['loadmore']);
const props = withDefaults(defineProps<PropsType>(), {
    empty: 0,
});
const loadMoreTrigger = ref<HTMLElement | null>(null);
const observer = ref<IntersectionObserver | null>(null);

const createObserver = () => {
    if (observer.value) {
        observer.value.disconnect(); // Remove existing observer before creating a new one
    }

    observer.value = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting) {
                emit('loadmore');
                props.query.fetchNext();
            }
        },
        { rootMargin: '20px' }
    );

    nextTick(() => {
        if (loadMoreTrigger.value) {
            setTimeout(() => {
                if (loadMoreTrigger.value) {
                    observer.value?.observe(loadMoreTrigger.value);
                }
            }, 100);
        }
    });
};

// Watch `nextPage` to reattach observer when it updates
watch(
    () => props.query?.links?.next,
    (newValue1) => {
        if (newValue1) {
            createObserver();
        }
    }
);

onMounted(() => {
    if (props.query?.links?.next) {
        createObserver();
    }
});
</script>

<template>
    <div class="relative w-full min-h-80">
        <div v-if="query.fetching" class="flex-center absolute backdrop-blur inset-0">
            <Spinner class="w-8 h-8" />
        </div>
        <EmptyState v-else-if="query.meta?.total === 0 && empty === 0" class="w-full py-20 max-md:mt-5">
            <slot name="empty" />
        </EmptyState>
        <div v-else>
            <slot />
        </div>
        <div v-if="query.links?.next" ref="loadMoreTrigger" class="flex-center p-4">
            <Spinner class="w-8 h-8" />
        </div>
    </div>
</template>
