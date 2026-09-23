<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Page, Switch } from '@shared/components';
import { Card, Filters, DataTable } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { dateFormat } from '@shared/utils';
import {DeleteIcon, EditIcon, PlusIcon} from '@adersolutions/icons';
import { PlaceFormDrawer } from './partials';
import type { AreaType, PlaceType } from '@common/types';
import type { BulkActionType, DataListType } from '@shared/types';
type PropsType = { zone: AreaType; lieux: DataListType<PlaceType> };
type StateType = { selected: PlaceType | null | object };

const props = defineProps<PropsType>();

const state = reactive<StateType>({
    selected: null,
});

const tabs = [
    { name: 'Tout', id: 'all' },
    { name: 'Actif', id: '1' },
    { name: 'Archivé', id: '0' },
];

const headings = [{ name: 'Lieu' }, { name: 'Map' }, { name: 'Status' }, { name: 'Date de création' }];

const bulkActions: BulkActionType[] = [
    {
        label: 'Edit',
        icon: EditIcon,
        variant: 'success',
        onAction: (item: AreaType) => {
            state.selected = item;
        },
    },
    {
        label: 'Supprimer',
        icon: DeleteIcon,
        variant: 'danger',
        confirm: {
            url: routes.settings.locations.places.destroy,
        },
    },
];

const pageAction = [
    {
        label: 'Ajouter un Lieu',
        icon: PlusIcon,
        variant: 'secondary',
        onAction: () => {
            state.selected = {};
        },
    },
];
</script>

<template>
    <Page :title="`Zones: ${zone.name}`" width="lg" back :actions="pageAction">
        <Card block :separated="false">
            <Filters :tabs="tabs" :default-tab="tabs[1].id" key-tab="status" />

            <DataTable v-slot="{ item }" :headings="headings" :items="lieux" :bulk-actions="bulkActions">
                <td class="cell">
                    {{ item.name }}
                </td>
                <td class="cell">
                    <a v-if="item.url" :href="item.url" target="_blank" class="text-blue-500 underline">Voir la carte</a>
                </td>
                <td class="cell">
                    <Switch :model-value="item.status" class="pointer-events-none" />
                </td>
                <td class="cell">
                    {{ dateFormat(item.created_at, 'fr-full') }}
                </td>
            </DataTable>
            <PlaceFormDrawer :item="state.selected" :zone="zone" @close="state.selected = null" />
        </Card>
    </Page>
</template>
