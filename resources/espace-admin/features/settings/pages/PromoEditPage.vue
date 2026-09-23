<script setup lang="ts">
import { PromoForm } from './partials';
import { routes } from '@espace-admin/routes';
import { Page } from '@shared/components';

defineProps({
    page: {
        type: Object,
        default: () => ({}),
    },
    offers: {
        type: Object,
        default: () => ({}),
    },
});
</script>
<template>
    <Page width="lg" title="Tous les Promo" :back="route(routes.pages.promo.index)">
        <PromoForm :offers="offers" :page="page" is-edit />
    </Page>
</template>
