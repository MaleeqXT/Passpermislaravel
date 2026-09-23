<script setup>
defineProps({
    imgTitle: String,
    imgAgency: String,
    gmap: String,
    leftClass: String,
    rightClass: String,
    dark: Boolean,
});
</script>

<template>
    <ul class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-6 lg:grid-rows-1 max-w-screen-xl mx-auto px-8 relative">
        <li
            :class="[
                'flex p-px lg:col-span-4 overflow-hidden rounded-lg bg-white ring-1  ring-dark/15 w-full shadow-box border-b-8 border-dark2 border',
                leftClass,
            ]"
        >
            <iframe
                :src="gmap"
                width="100%"
                height="100%"
                style="border: 0"
                allowfullscreen
                loading="lazy"
                class="h-full min-h-80 w-full object-cover"
                referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
        </li>
        <li
            :class="[
                'flex p-px lg:col-span-2 overflow-hidden rounded-lg  border-b-8 border-dark2  shadow-box flex-col',
                rightClass,
                dark ? 'bg-dark text-white' : 'bg-white ring-1 ring-dark/15',
            ]"
        >
            <img :src="imgAgency" alt="" class="w-full" />
            <img :src="imgTitle" class="h-10 px-3 w-fit -mt-14 mx-auto" alt="" />
            <div class="p-4 pt-8 flex flex-col gap-5">
                <slot />
            </div>
        </li>
    </ul>
</template>
