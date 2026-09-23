<script setup lang="ts">
import { DataTable, Page } from '@shared/components';
import { ShieldCheckMarkIcon, EditIcon, PlusIcon, WifiIcon, ExportIcon } from '@adersolutions/icons';
import { dateFormat } from '@shared/utils';
import { Card, DialogConfirm, Filters } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useRoute } from '@shared/hooks/index';
import { GeneralStatus, GeneralStatusEnum } from '@common/enums';
import { ArchiveIcon, UndoIcon } from '@adersolutions/icons';
import Badge from '@shared/components/feedbackIndicator/Badge.vue';
import { ItemImage } from '@common/components';
import type { UserType } from '@common/types';
import type { BulkActionType, DataListType } from '@shared/types';

type PropsType = { monitors: DataListType<UserType> };

const params = useRoute();
defineProps<PropsType>();

const confirmation = ref(null);
const deleting = ref(false);
const isRestore = ref(false);
const isInscriptionTab = ref(params.status === '3');
const tabs = [
    { name: 'Tout', id: 'all' },
    { name: 'Actif', id: '1' },
    { name: 'Archivé', id: '2' },
    { name: 'Inscription Moniteur', id: '3' },
];
const actions = [
    {
        label: 'Nouveau Moniteur',
        variant: 'primary',
        icon: PlusIcon,
        href: route(routes.users.monitors.create),
    },
];

const onConfirm = (monitor, restore = false) => {
    isRestore.value = restore;
    confirmation.value = monitor;
};

const onConfirmedDelete = async () => {
    deleting.value = true;
    let SelectedStatus = GeneralStatusEnum.INACTIVE;

    if (isRestore.value === true) {
        SelectedStatus = GeneralStatusEnum.ACTIVE;
    }
    await axios
        .put(route(routes.api.user.archive, confirmation.value?.id), {
            status: SelectedStatus,
        })
        .then(() => {
            confirmation.value = null;
            router.visit(route(routes.users.monitors.index, { status: 1 }), {
                method: 'get',
            });
        })
        .finally(() => {
            deleting.value = false;
        });
};
const getContent = () => {
    switch (confirmation.value?.status) {
        case GeneralStatusEnum.ACTIVE:
            return {
                // btn: item.name,
                content: 'Archiver',
                icon: ArchiveIcon,
                attrs: 'danger',
            };
        case GeneralStatusEnum.INACTIVE:
            return {
                // btn: item.name,
                content: 'Restore',
                icon: UndoIcon,
                attrs: 'info',
            };
        case GeneralStatusEnum.INPROGRESS:
            return {
                // btn: item.name,
                content: isRestore.value ? 'Activer' : 'Archiver',
                icon: isRestore.value ? ShieldCheckMarkIcon : ArchiveIcon,
                attrs: isRestore.value ? 'success' : 'yellow',
            };
        default:
            return {};
    }
};

const getActions = (item: UserType): BulkActionType[] | void => {
    const archived: BulkActionType = {
        label: 'Archive',
        icon: ArchiveIcon,
        variant: 'danger',
        onAction: () => onConfirm(item),
    };

    const restore: BulkActionType = {
        label: item.status == GeneralStatusEnum.INACTIVE ? 'Restore' : 'Activer',
        icon: UndoIcon,
        variant: 'danger',
        onAction: () => onConfirm(item, true),
    };

    switch (Number(item.status)) {
        case GeneralStatusEnum.ACTIVE:
            return [
                {
                    label: 'Modifier',
                    icon: EditIcon,
                    href: route(routes.users.monitors.edit, item.monitor?.id || '-'),
                    variant: 'success',
                },
                {
                    label: 'Export',
                    icon: ExportIcon,
                    onAction: () => {
                        window.location.href = route('admin.users.monitors.export.excel', item.monitor?.id || '-');
                    },
                    variant: 'success',
                },

                {
                    label: 'Connecter',
                    variant: 'secondary',
                    icon: WifiIcon,
                    self: true,
                    href: route(routes.impersonate.start, item.id || '-'),
                },
                archived,
            ];
        case GeneralStatusEnum.INACTIVE:
            return [restore];
        case GeneralStatusEnum.INPROGRESS:
            return [restore, archived];
        default:
            return [];
    }
};
</script>

<template>
    <Page :actions="actions" title="Moniteurs" width="xl">
        <Card block>
            <div>
                <Filters
                    :options="{ showSlot: false }"
                    :tabs="tabs"
                    :default-tab="tabs[1]?.id"
                    key-tab="status"
                    @change="isInscriptionTab = $event.status === '3'"
                />
                <DataTable
                    v-slot="{ item }"
                    :headings="[{ name: 'Moniteur' }, { name: 'Ville' }, { name: 'Secteur de conduite préféré' }, { name: 'Status' }]"
                    :items="monitors"
                    :bulk-actions="getActions"
                >
                    <td class="cell">
                        <ItemImage
                            :title="item.name"
                            :content="item?.email"
                            :phone="item?.phone"
                            :src="item.media || item.profile_photo_url"
                            :href="item.status !== GeneralStatusEnum.INACTIVE ? route(routes.users.monitors.edit, item.monitor.id) : ''"
                        />
                    </td>

                    <td class="cell">
                        {{ item.ville }}
                    </td>

                    <td class="cell">
                        {{ item.monitor.details.zone_souhaitee }}
                    </td>
                    <td class="cell">
                        <Badge :id="item.status" :options="GeneralStatus" />
                    </td>
                </DataTable>
            </div>
        </Card>
        <DialogConfirm
            :loading="deleting"
            :show="!!confirmation"
            :button-attrs="getContent().attrs"
            :icon="getContent().icon"
            @close="confirmation = null"
            @confirm="onConfirmedDelete"
        >
            Etes-vous sûr que vous voulez
            {{ getContent().content }} le moniteur
            <b class="text-red-500">{{ confirmation?.title }}</b>
            ?
        </DialogConfirm>
    </Page>
</template>
