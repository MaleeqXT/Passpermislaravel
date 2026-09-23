<script setup lang="ts">
import { routes } from '@espace-admin/routes';
import { dateFormat, getFilePath } from '@shared/utils';
import { GeneralStatusEnum } from '@common/enums';
import { Card, Filters, Page, DataTable, Badge, Popup } from '@shared/components';
import { AreaSelectionDialog, ItemImage } from '@common/components';
import { actions, headings, bulkActions, tabs } from './students';
import type { DataListType } from '@shared/types';
import type { GlobalDataType, UserType } from '@common/types';
import { useRoute } from '@shared/hooks';

type PropsType = {
    users: DataListType<UserType>;
};
defineProps<PropsType>();
const params = useRoute();
type SelectedLocationType = {
    area?: GlobalDataType | null;
    place?: GlobalDataType | null;
} | null;
const onLocationChange = (location: SelectedLocationType) => {
    // params.location = location;
    console.log('Selected location:', location);
    params.set({
        zone_id: location?.area?.id || null,
        lieu_id: location?.place?.id || null,
        ca: location?.area?.name || null,
        cp: location?.place?.name || null,
    });
};

// const panelActive = ref(null);
</script>

<template>
    <Page :actions="actions" title="Candidats" width="lg">
        <Card block>
            <div>
                <Filters :options="{ showSlot: false }" :tabs="tabs" default-tab="1" key-tab="status">
                    <AreaSelectionDialog
                        :model-value="{
                            area: params.zone_id ? { id: params.zone_id, name: params.ca } : null,
                            place: params.lieu_id ? { id: params.lieu_id, name: params.cp } : null,
                        }"
                        dw
                        @change="onLocationChange"
                    />
                </Filters>
                <DataTable v-slot="{ item }" :headings="headings" :items="users" :bulk-actions="bulkActions">
                    <td class="cell">
                        <ItemImage
                            :title="item.name"
                            :src="getFilePath(item)"
                            :status="item.status"
                            :href="item.status == GeneralStatusEnum.INACTIVE ? '' : route(routes.users.students.general, item.student.id)"
                        />
                    </td>
                    <td class="cell">{{ item.student?.balance }}h</td>
                    <td class="cell text-center px-6 space-x-1">
                        <Popup hoverable position="top">
                            <template v-for="(zone, index) in item.student?.zones" :key="index">
                                <Badge v-for="(lieu, index) in zone.lieux" :key="'lieux-' + index" variant="warning" :value="lieu" />
                            </template>
                            <template #content>
                                <div class="flex flex-wrap gap-1 p-2 max-w-xs">
                                    <div class="block w-full font-bold pb-1">Code Postal</div>
                                    <template v-for="(zone, index) in item.student?.zones" :key="index">
                                        <Badge v-for="(zip, index) in zone.zips" :key="'zips-' + index" :value="{ name: zip.code }" />
                                    </template>
                                </div>
                            </template>
                        </Popup>
                    </td>
                    <td class="cell">
                        {{ dateFormat(item.created_at, 'letter') }}
                    </td>
                </DataTable>
            </div>
        </Card>
    </Page>
</template>
