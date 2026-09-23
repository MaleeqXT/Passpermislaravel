<script setup lang="ts">
import { ref, reactive } from 'vue';
import { routes } from '@espace-secretary/routes';
import { Link } from '@inertiajs/vue3';
import { ButtonsList, Card, DialogConfirm, DateRangepicker, Drawer, Filters, Select, Page, Badge, DataTable } from '@shared/components';
import { EditIcon, PlusIcon, DeleteIcon } from '@adersolutions/icons';
import { dateFormat } from '@shared/utils';
import { CPFStatus, TabAll } from '@common/enums';
import { CPFUpdateForm, CPFStoreForm } from './partials';
import { useRoute } from '@shared/hooks';
import { BulkActionType } from '@shared/types';

defineProps({
    cpfs: {
        type: Object,
        default: () => ({}),
    },
});
const params = useRoute();
const state = reactive({
    delete: null,
    showDrawer: false,
    edit: null,
});
const actions = [
    {
        label: 'Nouveau Cpf',
        variant: 'primary',
        icon: PlusIcon,
        onAction: () => {
            state.showDrawer = true;
        },
    },
];
const bulkActions: BulkActionType[] = [
    {
        label: 'Modifier',
        icon: EditIcon,
        onAction: (item) => {
            state.edit = item;
        },
    },
    {
        label: 'Supprimer',
        icon: DeleteIcon,
        variant: 'danger',
        confirm: {
            url: routes.cpf.destroy,
        },
    },
];
const headings = [
    { name: 'Nom' },
    { name: 'Offer' },
    { name: 'Date Début formation' },
    { name: 'Date Fin formation' },
    { name: 'Statut' },
    { name: 'Vérifiction' },
    { name: 'Commentaire' },
];
const onDrawerClose = () => {
    state.showDrawer = false;
    state.edit = null;
};
const onChangeStatus = (value) => {
    params.set({ status: value });
};
</script>

<template>
    <Page :actions="actions" title="CPFs" width="xl">
        <Card block>
            <div>
                <Filters :tabs="[TabAll, ...Object.values(CPFStatus)]">
                    <DateRangepicker class="w-full max-w-64" placeholder="Date Vérifiction" />
                </Filters>
                <DataTable v-slot="{ item }" :headings="headings" :items="cpfs" :bulk-actions="bulkActions">
                    <td class="cell">
                        <Link :href="route(routes.users.students.general, item.student_id)" class="btn btn-link btn-info">
                            {{ item.student?.user?.name }}
                        </Link>
                    </td>

                    <td class="cell">
                        <Link :href="route(routes.shop.offers.edit, item.offer_id)" class="btn btn-link btn-dark">
                            {{ item.offer?.name }}
                        </Link>
                    </td>
                    <td class="cell">
                        <span> {{ dateFormat(item.start_at, 'letter') }} </span>
                    </td>
                    <td class="cell">
                        <span> {{ dateFormat(item.end_at, 'letter') }} </span>
                    </td>

                    <td class="cell">
                        <Badge :id="item.status" class="text-[12px] tracking-tight" :options="CPFStatus" />
                    </td>
                    <td class="cell">
                        <span v-if="item.date_verif">
                            {{ dateFormat(item.date_verif, 'letter') }}
                        </span>
                    </td>
                    <td class="cell">
                        <div class="line-clamp-2 max-w-60 text-wrap text-xs" v-html="item.comment"></div>
                    </td>
                </DataTable>
            </div>
        </Card>
        <Drawer :show="state.showDrawer || !!state.edit" @close="onDrawerClose">
            <CPFUpdateForm v-if="state.edit" :key="state.showDrawer || !!state.edit" :data="state.edit" @close="state.edit = null" />
            <CPFStoreForm v-show="!state.edit" :key="state.showDrawer || !!state.edit" @close="state.showDrawer = false" />
        </Drawer>
    </Page>
</template>
