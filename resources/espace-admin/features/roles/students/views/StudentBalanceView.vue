<script setup lang="ts">
import { routes } from '@espace-admin/routes';
import { Card, Button, InlineConfirm, Select, Badge, DataTable, Thumb, Back, ButtonGroup, EmptyState, Alerts } from '@shared/components';
import { useInertia, useQuery } from '@shared/hooks';
import { dateFormat, getFilePath } from '@shared/utils';
import { GeneralStatus } from '@common/enums';
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { DeleteIcon, AlertDiamondIcon } from '@adersolutions/icons';
import ViewContainer from './ViewContainer.vue';
import { ItemImage } from '@common/components';
import type { AreaType, UserType } from '@common/types';

type PropsType = {
    user: UserType;
    balances: any;
};

const props = defineProps<PropsType>();

const form = useInertia<{
    zone_id: string | null;
}>({
    zone_id: null,
});
const headings = [{ name: 'Offre' }, { name: 'Tranche' }, { name: 'Balance' }];
const zonesQuery = useQuery(
    {
        url: route(routes.api.locations.area.index),
        callback: (data) => [...data].filter((zone: AreaType) => !props.user?.student?.zones.some((z) => z.id === zone.id)),
    },
    true
);

const onStore = () => {
    if (!props.user?.student?.zones.some((zone) => zone.id === form.zone_id)) {
        form.post(route(routes.settings.locations.area.attach, props.user?.student?.id), {
            onSuccess: () => {
                form.zone_id = null;
                zonesQuery.fetch();
            },
        });
    }
};
const onDelete = (id: string) => {
    form.zone_id = id;
    form.post(route(routes.settings.locations.area.detach, props.user?.student?.id), {
        onSuccess: () => {
            form.zone_id = null;
            zonesQuery.fetch();
        },
    });
};
</script>

<template>
    <ViewContainer :user="user">
        <div class="form-page">
            <div class="left-form">
                <Back title="Condidat Balance et zone" :back="route(routes.users.students.index)" />
                <div class="form-content">
                    <Alerts />
                    <Card block>
                        <DataTable v-slot="{ item }" :headings="headings" :items="balances">
                            <td class="cell">
                                <ItemImage
                                    :src="getFilePath(item.offer, true)"
                                    :href="route(routes.shop.offers.edit, item.offer_id)"
                                    :title="item.offer?.name"
                                />
                            </td>
                            <td class="cell">
                                {{ item.offer.multi_payment || 1 }}
                            </td>
                            <td class="cell">{{ item.balance }} h</td>
                        </DataTable>
                    </Card>
                </div>
            </div>
            <div class="right-form">
                <div>
                    <Select
                        :key="zonesQuery.fetching ? '1' : '2'"
                        v-model="form.zone_id"
                        label="Zones"
                        class="min-w-[12rem]"
                        clear
                        placeholder="selectionner une zone"
                        :query="zonesQuery"
                    />
                    <div class="flex flex-col justify-between px-2">
                        <h3 class="text-sm text-gray-900">Les zones disponible pour {{ user.name }}</h3>
                        <ul v-if="user?.student?.zones.length" class="flex mt-2 flex-wrap border-y divide-y">
                            <li
                                v-for="zone in user?.student?.zones"
                                :key="zone.id"
                                class="text-sm font-semibold flex items-center w-full py-1"
                            >
                                <div class="flex-1">
                                    {{ zone.name }}
                                </div>
                                <InlineConfirm class="group" :loading="form.processing" @confirm="onDelete(zone.id)">
                                    <DeleteIcon
                                        title="retire zone"
                                        class="w-8 p-1.5 group-hover:bg-gray-200 rounded-lg text-red-500 duration-300 cursor-pointer"
                                    />
                                </InlineConfirm>
                            </li>
                        </ul>
                        <EmptyState v-else class="py-20" />
                    </div>
                </div>
                <ButtonGroup
                    vertical
                    class="form-actions"
                    :actions="[
                        {
                            label: 'Nouveau zone',
                            variant: 'primary',
                            full: true,
                            onAction: onStore,
                            loading: form.processing,
                            disabled: !form.zone_id,
                        },
                    ]"
                />
            </div>
        </div>
    </ViewContainer>
</template>
