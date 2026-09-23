<script setup lang="ts">
import { reactive } from 'vue';
import { Card, DateRangepicker, Filters, DataTable, Badge, Back, Button, Alerts } from '@shared/components';
import { MonitorRapportDialog } from '@espace-admin/components';
import { dateFormat, moneyFormat, strip } from '@shared/utils';
import { routes } from '@espace-admin/routes';
import ViewContainer from './ViewContainer.vue';
import type { MonitorType } from '@common/types';

type PropsType = {
    monitor: MonitorType;
    invoices: any;
};

defineProps<PropsType>();
const state = reactive({
    selectedItem: null,
});

const headings = [
    { name: 'Mois' },
    { name: 'Numéro' },
    { name: 'Heure travaillé' },
    { name: 'Total' },
    { name: 'Date de paiement' },
    { name: 'Statut' },
];
const tabs = [
    {
        name: 'Actif',
        id: '1',
    },
    {
        name: 'Archivé',
        id: '0',
    },
];
</script>

<template>
    <ViewContainer :monitor="monitor">
        <div class="px-5 max-w-screen-lg mx-auto">
            <Back :back="route(routes.users.monitors.index)" title="Les factures" />

            <Card block :separated="false">
                <Filters :options="{ showSlot: false }" :tabs="tabs" :default-tab="tabs[0].id" key-tab="status">
                    <DateRangepicker class="max-w-60" placeholder="Date de la facture" title="Filtre par date" />
                </Filters>
                <DataTable
                    v-slot="{ item }"
                    :headings="headings"
                    :items="invoices"
                    :bulk-actions="[
                        {
                            label: 'Rapport des heures',
                            onAction: (item) => {
                                state.selectedItem = item;
                            },
                        },
                    ]"
                >
                    <td class="cell">
                        <b>{{ dateFormat(item.created_at, 'month') }}</b>
                    </td>
                    <td class="cell">
                        <Button :href="route(routes.invoices.view, item.id)" link variant="primary">
                            <b>{{ item.num_facture }}</b>
                        </Button>
                    </td>

                    <td class="cell">{{ strip(item.details?.num_heures) }} h</td>
                    <td class="cell">
                        {{ moneyFormat(item.details?.total) }}
                    </td>
                    <td class="cell">
                        {{ dateFormat(item.date_paiement, 'letter') }}
                    </td>
                    <td class="cell">
                        <Badge
                            :value="{
                                name: item.date_paiement ? 'Payé' : 'En attente',
                                class: item.date_paiement ? 'success' : 'warning',
                            }"
                        />
                    </td>
                </DataTable>
                <!-- <MonitorRapportDialog :item="state.selectedItem" @close="state.selectedItem = null" /> -->
                <MonitorRapportDialog :item="state.selectedItem" @close="state.selectedItem = null" />
            </Card>
        </div>
    </ViewContainer>
</template>
