<script setup lang="ts">
import { TabSwitch } from '@shared/components';
import { DataView, PageMobile } from '@common/components';
import { ref } from 'vue';
import { CancellationItem, DetailsAnnulationModal } from './partials';
import { routes } from '@espace-monitor/routes';
import { useQuery, useRoute } from '@shared/hooks';
import { CancellationsType, ReservationType } from '@common/types';
import { useReservationDetails } from '@espace-monitor/stores';
import { ReservationDetailsDrawer } from '@espace-monitor/components';

const tabs = [
    { name: 'Tous', id: '' },
    { name: 'Justifié', id: '1' },
    { name: 'Non Justifié', id: '0' },
];
const details = useReservationDetails();
const params = useRoute<{ tab: string }>();
const cancellationsQuery = useQuery<CancellationsType[]>({
    url: route(routes.api.cancellations.index),
    params: { is_justified: params.tab || tabs[0].id },
    mounted: true,
});

const selectedTab = ref(params.tab || tabs[0].id);
const selected = ref(null);
const onAction = (item: CancellationsType) => {
    if (item.training?.reservation) {
        const res: ReservationType = item.training.reservation;
        res.training = item.training;
        details.open(res);
    }
};
const onTabChange = (tab: string) => {
    params.set({ tab: tab, page: 1 });
    cancellationsQuery.params.is_justified = tab;
    cancellationsQuery.fetch();
};
</script>

<template>
    <PageMobile :profile="true" title="Mes séances annulées" width="sm">
        <template #sticky>
            <TabSwitch v-model="selectedTab" :items="tabs" :default-tab="params.tab || ''" size="md" light full @change="onTabChange" />
        </template>
        <DataView :query="cancellationsQuery" class="pt-5">
            <ul role="list" class="grid gap-3 relative">
                <li :class="['bottom-20 absolute left-0 top-10 flex w-6 justify-center last']">
                    <div class="w-px bg-gray-300" />
                </li>
                <CancellationItem v-for="(item, date) in cancellationsQuery.data" :key="date" :item="item" @click="onAction(item)" />
            </ul>
        </DataView>
        <ReservationDetailsDrawer />
        <DetailsAnnulationModal :item="selected" @close="selected = null" />
    </PageMobile>
</template>
