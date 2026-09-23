<script setup lang="ts">
import { routes } from '@espace-admin/routes';
import { dateFormat } from '@shared/utils';
import { GeneralStatusEnum } from '@common/enums';
import { Card, Filters, Page, DataTable, Button } from '@shared/components';
import { AreaSelectionDialog, ItemImage } from '@common/components';
import { EditIcon, ArchiveIcon, UndoIcon, WifiIcon, PlusIcon } from '@adersolutions/icons';

import type { DataListType } from '@shared/types';
import type { GlobalDataType } from '@common/types';
import { useRoute } from '@shared/hooks';

type PropsType = {
    secretaries: DataListType<any>;
};


defineProps<PropsType>();

const params = useRoute();

type SelectedLocationType = {
    area?: GlobalDataType | null;
    place?: GlobalDataType | null;
} | null;

const onLocationChange = (location: SelectedLocationType) => {
    params.set({
        zone_id: location?.area?.id || null,
        lieu_id: location?.place?.id || null,
        ca: location?.area?.name || null,
        cp: location?.place?.name || null,
    });
};

//  Row actions for each secretary
const getActions = (item: any) => {
    return [
        {
            label: 'Modifier',
            icon: EditIcon,
            variant: 'success',
            onAction: () => {
                window.location.href = `/secretaries/${item.id}/edit`;
            },
        },
     
        {
            label: item.status == GeneralStatusEnum.INACTIVE ? 'Restore' : 'Archive',
            icon: item.status == GeneralStatusEnum.INACTIVE ? UndoIcon : ArchiveIcon,
            variant: item.status == GeneralStatusEnum.INACTIVE ? 'info' : 'danger',
            onAction: () => console.log('Archive/Restore clicked', item),
        },
    ];
};

//  Action button shown next to the page title
const headerActions = [
    {
        label: 'Créer Secrétaire',
        icon: PlusIcon,
        variant: 'primary',
        onAction: () => {
            window.location.href = '/secretaries/create';
        },
    },
];
</script>

<template>
    <Page title="Secrétaire" width="lg" :actions="headerActions">
        <Card block>
            <div>
                <Filters
                    :options="{ showSlot: false }"
                    :tabs="[
                        { name: 'Actif', id: '1' },
                        { name: 'Archivé', id: '2' },
                    ]"
                    default-tab="1"
                    key-tab="status"
                >
                    <AreaSelectionDialog
                        :model-value="{
                            area: params.zone_id ? { id: params.zone_id, name: params.ca } : null,
                            place: params.lieu_id ? { id: params.lieu_id, name: params.cp } : null,
                        }"
                        dw
                        @change="onLocationChange"
                    />
                </Filters>

                <DataTable
                    v-slot="{ item }"
                    :headings="[{ name: 'Nom' }, { name: 'Email' }, { name: 'Créé le' }]"
                    :items="secretaries"
                    :bulk-actions="getActions"
                >
                    <td class="cell">
                        <ItemImage
                            :title="item.user?.name"
                            :content="item.user?.email"
                            :src="item.user?.profile_photo_url"
                        />
                    </td>
                    <td class="cell">{{ item.user?.email }}</td>
                    <td class="cell">{{ dateFormat(item.created_at, 'letter') }}</td>
                </DataTable>
            </div>
        </Card>
    </Page>
</template>
