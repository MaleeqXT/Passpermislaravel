<script setup lang="ts">
import { Page } from '@shared/components';
import { PromoForm } from './partials';
import { routes } from '@espace-admin/routes';

defineProps({
    offers: {
        type: Object,
        default: () => ({}),
    },
});
</script>
<template>
    <Page width="lg" title="Tous les Promo" :back="route(routes.pages.promo.index)">
        <PromoForm :offers="offers" />
    </Page>
</template>
