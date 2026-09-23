<script setup lang="ts">
import { Page, Card, DateField, Filters, DataTable, Badge } from '@shared/components';
import { Link } from '@inertiajs/vue3';
import { routes } from '@espace-admin/routes';
import { dateFormat, moneyFormat } from '@shared/utils';
import { useRoute } from '@shared/hooks';
import MonitorRapportDialog from '@espace-admin/components/common/MonitorRapportDialog.vue';
import { reactive } from 'vue';
import { BulkActionType } from '@shared/types';

defineProps({
    invoices: {
        type: Object,
        default: () => ({}),
    },
});

const params = useRoute();
const state = reactive({
    selected: null,
});
const headings = [
    { name: 'Date' },
    { name: 'Nom' },
    { name: 'Salaire' },
    { name: 'Facturable' },
    { name: 'Non Facturable' },
    { name: 'Prix heure' },
    { name: 'Total ' },
    { name: 'status' },
];
const bullActions: BulkActionType[] = [
    {
        label: 'Voir Rapport Heures',
        variant: 'secondary',
        onAction: (item) => {
            state.selected = item;
        },
    },
    {
        label: 'Télécharger Facture',
        variant: 'info',
        external: true,
        isLink: true,
        onAction: ({ id }) => route('m.invoice', id),
    },
];
</script>

<template>
    <Page title="Factures " width="xl">
        <template #actions>
            <DateField
                v-model="params.period"
                class="max-w-40"
                filter
                month-picker
                :max-date="new Date()"
                clear
                @change="params.set({ period: $event }, { tableload: true })"
            />
        </template>
        <Card block :separated="false">
            <Filters />
            <DataTable v-slot="{ item }" :headings="headings" :items="invoices" :bulk-actions="bullActions">
                <td class="cell">
                    {{ dateFormat(item.from, 'month') }}
                </td>
                <td class="cell">
                    <Link class="font-bold btn btn-link !pl-0" :href="route(routes.users.monitors.edit, item.monitor?.id)">
                        {{ item.monitor?.user?.last_name }}
                    </Link>
                </td>
                <td class="cell">
                    {{ moneyFormat(item.montant) }}
                </td>
                <td class="cell">{{ moneyFormat(item.details?.num_heures_f || 0, '') }}h</td>
                <td class="cell">{{ moneyFormat(item.details?.num_heures_nf || 0, '') }}h</td>
                <td class="cell">
                    {{ moneyFormat(item.details?.prix_heure) }}
                </td>
                <td class="cell">
                    <div class="flex gap-1 font-bold text-xs">
                        <span
                            tooltip="Non facturable"
                            class="flex items-center text-orange-500 bg-orange-100 rounded-md shadow-sm px-2 w-fit py-0.5"
                        >
                            {{ moneyFormat(item.details?.num_heures_nf * item.details?.prix_heure) }}
                        </span>
                        <span
                            tooltip="Facturable"
                            class="flex items-center text-green-500 bg-green-100 rounded-md shadow-sm px-2 w-fit py-0.5"
                        >
                            {{ moneyFormat(item.details?.num_heures_f * item.details?.prix_heure) }}
                        </span>
                    </div>
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
        </Card>
        <MonitorRapportDialog :item="state.selected" @close="state.selected = null" />
    </Page>
</template>
