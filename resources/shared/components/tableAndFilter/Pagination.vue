<template>
    <div class="mx-auto flex-center text-dark gap-3 text-xs">
        <component
            :is="item.prev_page_url ? Link : 'div'"
            :class="[
                'flex items-center text-sm hover:text-primary bg-gray-200 p-1 rounded-l-lg',
                !item.prev_page_url && 'opacity-40 pointer-events-none',
            ]"
            :href="getLink(item.prev_page_url)"
        >
            <ChevronLeftIcon class="w-5" />
        </component>
        <div class="col-span-3 hidden md:flex gap-1 justify-center font-semibold">
            {{ item.from }} - {{ item.to }} sur {{ item.total }} éléments
        </div>
        <component
            :is="item.next_page_url ? Link : 'div'"
            :class="[
                'flex-center text-sm hover:text-primary bg-gray-200 p-1 rounded-r-lg',
                !item.next_page_url && 'opacity-50 pointer-events-none',
            ]"
            :href="getLink(item.next_page_url)"
        >
            <ChevronRightIcon class="w-5" />
        </component>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon, ChevronLeftIcon } from '@adersolutions/icons';
type PropsType = {
    item: {
        from: number;
        to: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    isApi?: boolean;
};
defineProps<PropsType>();

const getLink = ( url:string) => {

    const parsedUrl = new URL(location.href);
    const { page } = Object.fromEntries(new URL(url || location.href ).searchParams);
    if (page) {
        parsedUrl.searchParams.set('page', page);
    }
    return parsedUrl.href;
};
</script>
