<script setup lang="ts">
import { useSlots, computed } from 'vue';
import SpinnerIcon from '../feedbackIndicator/Spinner.vue';
import { Link } from '@inertiajs/vue3';
import type { ButtonType } from '@shared/types';

// Define props with type annotations
const props = defineProps<Omit<ButtonType, 'onAction'>>();
const hasSlot = computed(() => !!useSlots().default);
// Check if default slot is provided
const className = computed(() => [
    'btn relative',
    props.outline && 'btn-outline',
    props.link && 'btn-link',
    props.full && 'w-full justify-center',
    props.variant ? 'btn-' + props.variant : 'btn-default',
    props.class,
    !hasSlot.value && '!px-0 min-w-8 justify-center',
]);
</script>

<template>
    <!-- Link component -->
    <component
        :is="external || self ? 'a' : Link"
        v-if="href"
        :href="href"
        :target="external ? '_blank' : '_self'"
        :disabled="disabled || loading"
        :class="className"
    >
        <component :is="icon" v-if="icon" class="h-5 w-5" :class="{ '!-mx-': !hasSlot }" />
        <slot />
    </component>

    <!-- Button component -->
    <button v-else :type="submit ? 'submit' : 'button'" :loading="loading" :disabled="disabled || loading" :class="className">
        <!-- Loading Spinner -->
        <p v-if="loading" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
            <SpinnerIcon class="fill-primary w-5 h-5 text-gray-300 animate-spin" />
        </p>
        <!-- Button Content -->
        <p class="flex items-center gap-2" :class="{ 'opacity-0': loading }">
            <component :is="icon" v-if="icon" class="h-5 w-5" />
            <span v-if="hasSlot" class="mx-auto flex items-center gap-2">
                <slot />
            </span>
        </p>
    </button>
</template>
