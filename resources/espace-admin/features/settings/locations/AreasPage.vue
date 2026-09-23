<script setup lang="ts">
import { reactive } from 'vue';
import { Button, Page, Switch } from '@shared/components';
import { Card, Filters, DataTable } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { dateFormat } from '@shared/utils';
import { DeleteIcon, EditIcon, PlusIcon, ViewIcon } from '@adersolutions/icons';
import type { BulkActionType, DataListType } from '@shared/types';
import type { AreaType } from '@common/types';
import { AreaFormDrawer } from './partials';

// Define the shape of a zone object. Extend this interface as needed.
type PropsType = { zones: DataListType<AreaType> };
type StateType = { selected: AreaType | null | object };

// Define props with types and a default value using withDefaults.
const props = defineProps<PropsType>();
const state = reactive<StateType>({
    selected: null,
});

const tabs = [
    { name: 'Tout', id: 'all' }, // TabAll
    { name: 'Actif', id: '1' },
    { name: 'Archivé', id: '0' },
];
const pageAction = [
    {
        label: 'Ajouter une zone',
        icon: PlusIcon,
        variant: 'secondary',
        onAction: () => {
            state.selected = {};
        },
    },
];
const headings = [{ name: 'Zone' }, { name: 'Status' }, { name: 'Date de création' }];
const bulkActions: BulkActionType[] = [
    {
        label: 'List Lieux',
        icon: ViewIcon,
        variant: 'info',
        isLink: true,
        onAction: (item: AreaType) => route(routes.settings.locations.places.index, item.id),
    },
    {
        label: 'List Code Postal',
        icon: ViewIcon,
        variant: 'secondary',
        isLink: true,
        onAction: (item: AreaType) => route(routes.settings.locations.zip.index, item.id),
    },
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
            url: routes.settings.locations.area.destroy,
        },
    },
];
</script>

<template>
    <Page title="Zones" width="md" :actions="pageAction">
        <Card block :separated="false">
            <Filters :options="{ showSlot: false }" :tabs="tabs" :default-tab="tabs[1].id" key-tab="status" />
            <DataTable v-slot="{ item }" :headings="headings" :items="zones" :bulk-actions="bulkActions">
                <td class="cell">
                    <Button
                        :href="route(routes.settings.locations.places.index, item.id)"
                        link
                        tl
                        tooltip="Voir la liste des lieux pour ce zone"
                    >
                        {{ item.name }}
                    </Button>
                </td>
                <td class="cell">
                    <Switch :model-value="item.status" class="pointer-events-none" />
                    <!-- <Badge :id="item.status" :options="ActivationStatus" /> -->
                </td>
                <td class="cell">
                    {{ dateFormat(item.created_at, 'fr-full') }}
                </td>
            </DataTable>
        </Card>
        <AreaFormDrawer :item="state.selected" @close="state.selected = null" />
    </Page>
</template>
