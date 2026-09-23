<template>
    <component
        :is="as || 'div'"
        :class="['text-center text-gray-400 relative', block && 'bg-white shadow-box pt-5 pb-8 rounded-xl relative']"
    >
        <div v-if="loading" class="absolute inset-0 flex-center backdrop-blur-md">
            <Spinner class="w-6" />
        </div>
        <component :is="image" v-if="typeof image === 'function' || typeof image === 'object'" class="mx-auto h-12 w-12 fill-current" />
        <img
            v-else
            class="mx-auto object-contain w-[30%] text-gray-400 max-w-24"
            :src="image ?? '/assets/images/not-found.png'"
            :alt="heading || title"
        />
        <h3 class="mt-2 text-md font-semibold text-gray-900">{{ heading || title || 'Aucune donnée disponible' }}</h3>
        <div class="mt-1 text-xs text-gray-500">
            <slot />
        </div>
        <div v-if="actions.length" class="flex gap-3 justify-center mt-6">
            <Button v-for="action in actions" :key="action.name" v-bind="action" @click="action.onAction">
                {{ action.label || action.title }}
            </Button>
        </div>
    </component>
</template>

<script setup lang="ts">
import { PropType } from 'vue';
import Button from '../actions/Button.vue';
import { ButtonType } from '@shared/types';
import Spinner from './Spinner.vue';

defineProps({
    heading: String,
    as: String,
    title: String,
    block: Boolean,
    loading: Boolean,
    image: [String, Function, Object] as PropType<string | (() => void) | object>,
    actions: {
        type: Array as PropType<ButtonType[]>,
        default: () => [],
    },
});
</script>
