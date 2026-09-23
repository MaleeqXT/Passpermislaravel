<script setup lang="ts">
import { ref } from 'vue';
import { dateFormat } from '@shared/utils';
import { Card, DialogConfirm, Filters, Page, DataTable, Badge } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { router } from '@inertiajs/vue3';
import { GeneralStatus, GeneralStatusEnum } from '@common/enums';
import { ArchiveIcon, UndoIcon, EditIcon, PlusIcon } from '@adersolutions/icons';
import { ItemImage } from '@common/components';

defineProps({
    users: {
        type: Object,
        default: () => ({}),
    },
});
const confirmation = ref(null);
const deleting = ref(false);
const isRestore = ref(false);

const actions = [
    {
        label: 'Nouvel admin',
        variant: 'primary',
        icon: PlusIcon,
        href: route(routes.users.admins.create),
    },
];

const headings = [
    { name: 'Admin' },
    { name: 'Téléphone', className: 'text-center' },
    // { name: 'Genre', className: 'text-center' },
    { name: 'Status', className: 'text-center' },
    // { name: 'Ville', className: 'text-center' },
    { name: 'Date Inscription' },
    { name: 'Action', className: 'text-center pr-4 sticky right-0 bg-slate-100' },
];

const onConfirm = (student, restore = null) => {
    restore === true ? (isRestore.value = true) : (isRestore.value = false);
    confirmation.value = student;
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
            router.visit(route(routes.users.admins.index, { status: GeneralStatusEnum.ACTIVE }), {
                method: 'get',
            });
        })
        .finally(() => {
            deleting.value = false;
        });
};
</script>

<template>
    <Page :actions="actions" title="Administrateurs" width="lg">
        <Card block>
            <div>
                <Filters
                    :options="{ showSlot: false }"
                    :tabs="[
                        { name: 'Actif', id: '1' },
                        { name: 'Archivé', id: '2' },
                        { name: 'Tout', id: 'all' },
                    ]"
                    default-tab="1"
                    key-tab="status"
                />
                <DataTable
                    v-slot="{ item }"
                    :headings="headings"
                    :items="users"
                    :bulk-actions="
                        (item) => [
                            {
                                label: 'Modifier',
                                icon: EditIcon,
                                variant: 'success',
                                full: true,
                                href: route(routes.users.admins.edit, item?.id || '-'),
                            },

                            item.status == GeneralStatusEnum.ACTIVE
                                ? {
                                      label: 'Archive',
                                      icon: ArchiveIcon,
                                      variant: 'danger',
                                      onAction: () => onConfirm(item),
                                  }
                                : {
                                      label: 'restore',
                                      icon: UndoIcon,
                                      variant: 'danger',
                                      onAction: () => onConfirm(item, true),
                                  },
                        ]
                    "
                >
                    <td class="cell">
                        <ItemImage
                            :content="item?.email"
                            :title="item.name"
                            :src="item.media || item.profile_photo_url"
                            :href="item.status == GeneralStatusEnum.ACTIVE ? route(routes.users.admins.edit, item.id) : ''"
                        />
                    </td>

                    <td class="cell text-center">
                        {{ item.phone ?? '-' }}
                    </td>

                    <td class="cell text-center">
                        <Badge :id="item.status" :options="GeneralStatus" />
                    </td>

                    <td class="cell capitalize">
                        {{ dateFormat(item.created_at, 'fulltime') }}
                    </td>
                </DataTable>
            </div>
        </Card>
        <DialogConfirm
            :loading="deleting"
            :show="!!confirmation"
            :is-restore="isRestore"
            @close="confirmation = null"
            @confirm="onConfirmedDelete"
        >
            Etes-vous sûr que vous voulez {{ isRestore ? 'restore' : 'archive' }} l'Admin
            <b class="text-red-500">{{ confirmation?.title }}</b>
            ?
        </DialogConfirm>
    </Page>
</template>
