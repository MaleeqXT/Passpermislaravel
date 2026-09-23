<script setup lang="ts">
import { EmptyState } from '@shared/components';
import { InvoiceRowItem } from './partials';
import { reactive } from 'vue';
import { PageMobile } from '@common/components';
import { routes } from '@espace-monitor/routes';

defineProps({
    invoices: {
        type: Object,
        default: () => ({}),
    },
});

const state = reactive({
    selectedItem: null,
});
</script>

<template>
    <PageMobile title="Factures" subtitle="Liste des factures" back slided>
        <ul class="my-5 mx-auto max-w-xl w-full flex flex-col gap-3">
            <InvoiceRowItem v-for="item in invoices.data" :key="item.id" :item="item" @click:resume="state.selectedItem = $event" />
            <EmptyState
                v-if="!invoices.data.length"
                class="pb-5"
                as="li"
                heading="Aucun factures pour le moment"
                image="https://cdn.shopify.com/s/files/1/0262/4071/2726/files/emptystate-files.png"
            >
                <p>Vous n'avez pas encore de factures.</p>
            </EmptyState>
        </ul>
    </PageMobile>
</template>
