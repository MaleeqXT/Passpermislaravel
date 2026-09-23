<script setup lang="ts">
import { Card, Filters, DataTable, Badge, Page } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { reactive } from 'vue';
import { DeleteIcon, EditIcon, PlusIcon } from '@adersolutions/icons';
import { ActivationStatus } from '@common/enums';
import { CompetencyFormDrawer } from './partials';
import type { CompetencyType, CompetencyGroupType } from '@common/types';
import type { BulkActionType, DataListType } from '@shared/types';

type PropsType = { subCompetencies: DataListType<CompetencyType>; group: CompetencyGroupType };
type StateType = { selected: CompetencyType | object | null };

const props = defineProps<PropsType>();

const state = reactive<StateType>({
    selected: null,
});
const headings = [{ name: '#', className: 'min-w-5' }, { name: 'Label' }, { name: 'Statut' }];
const tabs = [
    { name: 'Tout', id: '' },
    { name: 'Actif', id: '1' },
    { name: 'Archivé', id: '0' },
];
const pageActions = [
    {
        label: 'Nouvelle compétence',
        icon: PlusIcon,
        variant: 'primary',
        onAction: () => {
            state.selected = {
                main_competency_id: props.group.id,
            };
        },
    },
];
const bulckActions: BulkActionType[] = [
    {
        label: 'Modifier',
        icon: EditIcon,
        onAction: (item: CompetencyType) => {
            state.selected = item;
        },
    },
    {
        label: 'Supprimer',
        icon: DeleteIcon,
        variant: 'danger',
        confirm: {
            message: 'Voulez-vous vraiment supprimer cet competence',
            url: routes.settings.competences.group.destroy,
        },
    },
];
</script>

<template>
    <Page :title="group.name" width="md" back :actions="pageActions">
        <Card block :separated="false">
            <Filters :options="{ showSlot: false }" :tabs="tabs" :default-tab="tabs[0].id" key-tab="status" />
            <DataTable v-slot="{ item }" :headings="headings" :items="subCompetencies" :paginate="false" :bulk-actions="bulckActions">
                <td class="cell !min-w-0 max-w-5">
                    <b>
                        {{ item.position }}
                    </b>
                </td>

                <td class="cell">
                    {{ item.label }}
                </td>

                <td class="cell">
                    <Badge :id="item.status ? 1 : 2" :options="ActivationStatus" />
                </td>
            </DataTable>
        </Card>
        <CompetencyFormDrawer :item="state.selected" :length="subCompetencies.data?.length" @close="state.selected = null" />
    </Page>
</template>
