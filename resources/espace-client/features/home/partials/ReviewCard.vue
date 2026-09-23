<template>
    <figure class="rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-900/5 max-md:w-[20rem]">
        <blockquote class="text-gray-900">
            <p class="line-clamp-5">{{ `“${review.body}”` }}</p>
        </blockquote>
        <figcaption class="mt-6 flex items-center gap-x-4">
            <img class="size-10 rounded-full bg-gray-50" :src="review.author.imageUrl" alt="" />
            <div>
                <div class="font-semibold">{{ review.author.name }}</div>
                <div class="text-gray-600">{{ `@${review.author.handle}` }}</div>
            </div>
        </figcaption>
    </figure>
</template>

<script setup>
defineProps(['review']);
</script>
