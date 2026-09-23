<script setup lang="ts">
import { Page, Card, Filters, DataTable } from '@shared/components';
import { MonitorRapportDialog } from '../../../components/common';
import { InvoiceRowItem } from './partials';
import { reactive } from 'vue';

defineProps({
    invoices: {
        type: Object,
        default: () => ({}),
    },
});
const state = reactive({
    selectedItem: null,
});
const tabs = [
    {
        name: 'Factures actif',
        id: '1',
    },
    {
        name: 'Factures Archivés',
        id: '0',
    },
];
const headings = [
    { name: 'Numéro facture' },
    { name: 'Départements' },
    { name: 'Nom de Famile' },
    { name: 'Numéro d’autorisation' },
    { name: 'ETP B' },
    { name: 'Heure Travaillé', className: 'text-center' },
    { name: 'Salaire', className: 'text-center' },
    { name: 'Date de paiement', className: 'text-center' },
    { name: 'Statut', className: 'text-center' },
    { name: 'Action', className: 'text-center sticky right-0 bg-gray-50 border-l' },
];
</script>

<template>
    <Page title="Factures" width="xl">
        <Card block :separated="false">
            <Filters :options="{ showSlot: false }" :tabs="tabs" :default-tab="tabs[0].id" key-tab="status" />
            <DataTable :headings="headings" :items="invoices">
                <template #items="{ items }">
                    <InvoiceRowItem v-for="item in items" :key="item.id" :item="item" @click:resume-hours="state.selectedItem = $event" />
                </template>
            </DataTable>
            <MonitorRapportDialog :item="state.selectedItem" @close="state.selectedItem = null" />
        </Card>
    </Page>
</template>
