<template>
    <component
        :is="href ? (reload ? 'a' : Link) : 'button'"
        :href="href"
        type="button"
        :class="[
            'font-semibold h-11 md:h-12 px-3 md:px-5 rounded-lg btn-m flex-center relative',
            disabled ? 'bg-gray-300 text-gray-500 pointer-events-none' : variantClass,
        ]"
        :disabled="disabled"
    >
        <div v-if="loading" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
            <Spinner class="fill-primary w-5 h-5 text-gray-300 animate-spin" />
        </div>
        <div class="flex-center text-md md:text-lg" :class="{ 'opacity-0': loading }"><slot /></div>
    </component>
</template>
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Spinner } from '@shared/components';
import { computed } from 'vue';

type PropsType = {
    loading?: boolean;
    reload?: boolean;
    disabled?: boolean;
    href?: string;
    variant?: 'primary' | 'secondary' | 'danger' | 'outline' | 'warning' | 'success' | 'link' | 'link-dark' | 'info' | 'dark' | 'light';
};
const props = withDefaults(defineProps<PropsType>(), {
    variant: 'primary',
});

const variantClass = computed(() => {
    switch (props.variant) {
        case 'primary':
            return 'text-white bg-primary hover:bg-teal-700 border-b-4 border-dark2 active:border-b-0';
        case 'secondary':
            return 'text-white bg-secondary hover:bg-teal-700 border-b-4 border-dark2 active:border-b-0';
        case 'danger':
            return 'text-white bg-red-500 hover:bg-red-700 border-b-4 border-red-900 active:border-b-0';

        case 'warning':
            return 'text-white bg-yellow-500 hover:bg-yellow-700 border-b-4 border-yellow-900 active:border-b-0';
        case 'success':
            return 'text-white bg-green-500 hover:bg-green-700 border-b-4 border-green-900 active:border-b-0';
        case 'link':
            return 'text-primary bg-white hover:bg-gray-100 ';
        case 'link-dark':
            return 'text-dark2  hover:text-primary ';
        case 'info':
            return 'text-white bg-sky-400 hover:bg-sky-700 border-b-4 border-sky-900 active:border-b-0';
        case 'dark':
            return 'text-white bg-dark2 hover:bg-gray-900 border-b-4 border-gray-900 active:border-b-0';
        case 'light':
            return 'text-gray-800 bg-white hover:bg-gray-100 border-b-4 border-gray-300 active:border-b-0';
        case 'outline':
            return 'text-primary bg-white ring ring-primary hover:text-white hover:bg-primary';
        default:
            return 'text-white bg-primary hover:bg-teal-700 border-b-4 border-dark2 active:border-b-0';
    }
});
</script>
