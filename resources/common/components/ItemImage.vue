<template>
    <div :class="['flex gap-3 items-center leading-5 w-fit']">
        <component :is="href ? Link : 'div'" v-if="src" :href="href" :class="['inline-block', size, href && 'hover:scale-105 t-2 btn-m']">
            <img :class="[size, 'object-contain border rounded-lg group-hover/link:cursor-pointer bg-white']" :src="src" :alt="title" />
        </component>
        <div v-else :class="[size, 'flex-center border rounded-lg bg-gray-100']">
            <ImageIcon :class="['object-contain w-6 text-gray-600']" />
        </div>
        <component
            :is="href ? Link : 'div'"
            v-if="title"
            :href="href"
            :tooltip="href && 'Voir la Page'"
            class="mb-0 flex flex-col flex-1"
            @click="prevent"
        >
            <p :class="['line-clamp-1 w-fit', href && 'hover:underline !text-blue-500 btn-m']">
                {{ title }}
            </p>
        </component>
    </div>
</template>
<script setup lang="ts">
import { ImageIcon } from '@adersolutions/icons';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    src: String,
    title: String,
    href: String,
    size: {
        type: String,
        default: 'w-8 h-8',
    },
});
const prevent = (e: Event) => {
    props.href && e.stopPropagation();
};
</script>
