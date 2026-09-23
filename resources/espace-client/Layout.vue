<script setup lang="ts">
import { watch } from 'vue';
import { useAlert } from '@shared/stores';
import Header from './layouts/Header.vue';
import Footer from './layouts/Footer.vue';
import { Alerts } from '@shared/components';
import { PhoneIcon } from '@adersolutions/icons';
import { Link } from '@inertiajs/vue3';
import { routes } from '@espace-client/routes';

const props = defineProps({
    auth: Object,
    flash: Object,
});
const alert = useAlert();

watch(props, ({ flash }) => {
    if (flash.success || flash.error) {
        alert.show({
            title: flash.error || flash.success,
            type: flash.error ? 'error' : 'success',
        });
    }
});
</script>
<template>
    <div class="text-sm">
        <Header :key="$page.url" />
        <slot />
        <Footer />
        <Link :href="route(routes.contact.index)"
              class="fixed bottom-4 left-4 md:bottom-8 md:left-8 z-900 flex-center gap-2 drop-shadow">
            <PhoneIcon class="text-white w-12 h-12 bg-primary rounded-full p-1.5 shadow-lg" />
            <span class="bg-dark2 rounded-full text-lg uppercase font-bold px-3 py-2 text-white">Être Recontacté</span>
        </Link>
        <Alerts type="mobile" />
    </div>
</template>
