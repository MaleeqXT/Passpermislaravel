<script setup lang="ts">
import { Badge, Button, Page } from '@shared/components';
import { Card, Filters, DataTable } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { CompetencyGroupFormDrawer } from './partials';
import { ActivationStatus } from '@common/enums';
import { DeleteIcon, EditIcon, PlusIcon } from '@adersolutions/icons';
import { reactive } from 'vue';
import type { BulkActionType, DataListType } from '@shared/types';
import type { CompetencyGroupType } from '@common/types';

type PropsType = { groups: DataListType<CompetencyGroupType> };
type StateType = { selected: CompetencyGroupType | object | null };

defineProps<PropsType>();

const state = reactive<StateType>({
    selected: null,
});
const tabs = [
    { name: 'Tout', id: '' },
    { name: 'Actif', id: '1' },
    { name: 'Archivé', id: '0' },
];
const headings = [{ name: '#', className: 'min-w-5' }, { name: 'Nom' }, { name: 'Label' }, { name: 'Statut' }];

const pageActions = [
    {
        label: 'Nouvelle compétence',
        icon: PlusIcon,
        variant: 'primary',
        onAction: () => {
            state.selected = {};
        },
    },
];
const bulckActions: BulkActionType[] = [
    {
        label: 'Modifier',
        icon: EditIcon,
        onAction: (item: CompetencyGroupType) => {
            state.selected = item;
        },
    },
    {
        label: 'Supprimer',
        icon: DeleteIcon,
        variant: 'danger',
        confirm: {
            message: 'Voulez-vous vraiment supprimer ce groupe?',
            url: routes.settings.competences.group.destroy,
        },
    },
];
</script>

<template>
    <Page title="Compétences des Candidats" width="md" :actions="pageActions">
        <Card block :separated="false">
            <Filters :options="{ showSlot: false }" :tabs="tabs" :default-tab="tabs[0].id" key-tab="status" />
            <DataTable v-slot="{ item }" :headings="headings" :items="groups" :paginate="false" :bulk-actions="bulckActions">
                <td class="cell !min-w-0 max-w-5">
                    <b>
                        {{ item.position }}
                    </b>
                </td>
                <td class="cell">
                    <Button :href="route(routes.settings.competences.sub.index, item.id)" link tooltip="Voir la liste des compétences">
                        {{ item.name }}
                    </Button>
                </td>
                <td class="cell">
                    {{ item.label }}
                </td>

                <td class="cell">
                    <Badge :id="item.status ? 1 : 2" :options="ActivationStatus" />
                </td>
            </DataTable>
        </Card>
        <CompetencyGroupFormDrawer :item="state.selected" :length="groups.data?.length" @close="state.selected = null" />
    </Page>
</template>
