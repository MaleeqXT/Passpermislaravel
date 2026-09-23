<script setup lang="ts">
import { StarIcon, StarFilledIcon } from '@adersolutions/icons';
defineProps({
    item: Object,
});
const feedbacks = [
    { id: 1, label: 'En cours' },
    { id: 2, label: 'À améliorer' },
    { id: 3, label: 'Maîtrisé' },
];
</script>

<template>
    <li class="py-2 flex justify-between items-center gap-10">
        <div class="text-sm flex-1">
            <p class="font-semibold">{{ item.label }}</p>
            <p v-if="item.rating?.comment" class="opacity-70">
                {{ item?.rating?.comment }}
            </p>
        </div>

        <ul class="grid grid-cols-3 gap-3 text-sm text-center">
            <li v-for="feed in feedbacks" :key="feed.id">
                <component
                    :is="feed.id <= item.rating?.rating ? StarFilledIcon : StarIcon"
                    :class="['w-10 h-10  mx-auto', !item.rating?.rating ? 'text-gray-400' : 'text-yellow-500']"
                />
                <span class="text-2xs text-center text-gray-500">
                    {{ feed.label }}
                </span>
            </li>
        </ul>
    </li>
</template>
