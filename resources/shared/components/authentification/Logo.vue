<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
type PropsType = {
    black?: boolean;
};
defineProps<PropsType>();
</script>

<template>
    <Link :href="'/'">
        <img
            :src="black ? '/assets/logo-permis-facile.webp' : '/assets/logo-permis-facile.webp'"
            alt="ecole"
            class="w-full max-h-16 mx-auto"
        />
    </Link>
</template>
