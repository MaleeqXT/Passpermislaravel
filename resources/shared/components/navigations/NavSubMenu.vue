<template>
    <li>
        <component
            :is="subitem.reload ? 'a' : Link"
            :href="subitem.href"
            :target="subitem.external ? '_blank' : '_self'"
            :class="[
                isActive(subitem.href) && 'btn-header !justify-start font-bold border-l-0',
                !subitem.href && 'text-opacity-30 pointer-events-none',
                subitem.disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '',
                'group flex rounded-md px-2 py-1 text-sm pl-[36px] leading-6 relative active:opacity-75 hover:bg-white/10',
            ]"
        >
            <svg
                :class="[
                    'w-[18px] absolute left-[13.5px] top-1/2 -translate-y-1/2 isolate ',
                    isActive(subitem.href) ? 'text-white' : 'opacity-0 group-hover:opacity-30 text-white/90',
                ]"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    d="M19 14.0625L11 14.0625C7.68629 14.0625 6 12.3762 6 9.0625L6 7M19 14.0625L15.0625 18M19 14.0625L15.0625 10.125"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                ></path>
            </svg>
            <span
                v-if="isPassed || isActive(subitem.href)"
                :class="[
                    'w-[1.8px] absolute left-[17px] bg-white isolate',
                    isActive(subitem.href) ? '-top-1 bottom-1/2' : '-top-1 -bottom-4',
                ]"
            />
            <p class="leading-5 py-0.5">
                {{ subitem.name }}
            </p>
        </component>
    </li>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { isActive } from '@shared/utils';

// Define the shape of the subitem prop
interface SubItem {
    name: string;
    href?: string;
    reload?: boolean;
    external?: boolean;
    disabled?: boolean;
}

// Props interface
interface Props {
    subitem: SubItem;
    isPassed: boolean;
}

// Define props with TypeScript
defineProps<Props>();
</script>
