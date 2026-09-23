<script setup lang="ts">
import { reactive } from 'vue';
import { routes } from '@espace-secretary/routes';
import { Card, DataTable, Drawer, Page } from '@shared/components';
import { EditIcon, EmailIcon, EmailNewsletterIcon, PaperCheckIcon } from '@adersolutions/icons';
import { dateFormat } from '@shared/utils';
import { CPFoffres } from '@common/enums';
import { CPFFormUpdateForm } from './partials';
import { useRoute } from '@shared/hooks';
import { BulkActionType, DataListType } from '@shared/types';
import { useForm } from '@inertiajs/vue3';
type PropsType = {
    cpfs: DataListType<Record<string, any>>;
};
defineProps<PropsType>();
const params = useRoute();
const state = reactive({
    delete: null,
    showDrawer: false,
    edit: null,
});
const form = useForm({});

const bulkActions: BulkActionType[] = [
    {
        label: 'Modifier',
        variant: 'success',
        icon: EditIcon,
        onAction: (item) => {
            state.edit = item;
        },
    },
    {
        label: 'Envoyer documents ',
        icon: EmailIcon,
        variant: 'info',
        onAction: (item) => {
            form.post(route(routes.formCpf.documents, item.id), {
                preserveScroll: true,
            });
        },
    },
    {
        label: 'Attestation fin formation',
        icon: EmailNewsletterIcon,
        variant: 'orange',
        onAction: (item) => {
            form.post(route(routes.formCpf.finCPf, item.id), {
                preserveScroll: true,
            });
        },
    },
];
const headings = [
    { name: 'Nom' },
    { name: 'Offer' },
    { name: 'Date Début formation' },
    { name: 'Test de positionnement' },
    { name: 'Contact formation' },
    { name: "Attestation l'honneur" },
    { name: 'Reservations' },
    { name: 'Attestation fin formation' },
];
const onDrawerClose = () => {
    state.showDrawer = false;
    state.edit = null;
};
</script>

<template>
    <Page title="Form Cpf" width="xl">
        <Card block>
            <DataTable v-slot="{ item }" :headings="headings" :items="cpfs" :bulk-actions="bulkActions">
                <td class="cell">
                    {{ item?.test_pro?.name }}
                </td>

                <td class="cell">
                    {{ CPFoffres?.[item.boite?.id]?.[item?.offre]?.name }}
                </td>
                <td class="cell">
                    <span> {{ dateFormat(item.created_at, 'letter') }} </span>
                </td>

                <td class="cell">
                    <a target="_blank" :href="route(routes.formCpf.positionnement, item.id)">
                        <PaperCheckIcon :class="['object-contain w-6 text-primary cursor-pointer']" />
                    </a>
                </td>

                <td class="cell">
                    <a target="_blank" :href="route(routes.formCpf.contact, item.id)">
                        <PaperCheckIcon :class="['object-contain w-6 text-primary cursor-pointer']" />
                    </a>
                </td>
                <td class="cell">
                    <a v-if="item.attestation_honneur" target="_blank" :href="route(routes.formCpf.attestation, item.id)">
                        <PaperCheckIcon :class="['object-contain w-6 text-primary cursor-pointer']" />
                    </a>
                    <PaperCheckIcon v-else :class="['object-contain w-6 text-red-500 cursor-pointer']" />
                </td>
                <td class="cell">
                    <a v-if="item.reservations" target="_blank" :href="route(routes.formCpf.reservations, item.id)">
                        <PaperCheckIcon :class="['object-contain w-6 text-primary cursor-pointer']" />
                    </a>
                    <PaperCheckIcon v-else :class="['object-contain w-6 text-red-500 cursor-pointer']" />
                </td>
                <td class="cell">
                    <a v-if="item.reservations" target="_blank" :href="route(routes.formCpf.attestationFinFormation, item.id)">
                        <PaperCheckIcon :class="['object-contain w-6 text-primary cursor-pointer']" />
                    </a>
                    <PaperCheckIcon v-else :class="['object-contain w-6 text-red-500 cursor-pointer']" />
                </td>
            </DataTable>
        </Card>
        <Drawer :show="state.showDrawer || !!state.edit" @close="onDrawerClose">
            <CPFFormUpdateForm v-if="state.edit" :key="state.showDrawer || !!state.edit" :data="state.edit" @close="state.edit = null" />
        </Drawer>
    </Page>
</template>
