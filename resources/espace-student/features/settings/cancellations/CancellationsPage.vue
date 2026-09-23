<script setup lang="ts">
import { EmptyState, Spinner, TabSwitch } from '@shared/components';
import { DataView, PageMobile } from '@common/components';
import { ref, watch } from 'vue';
import { CancellationItem, CancelDetailsDrawer } from './partials';
import { router } from '@inertiajs/vue3';
import { routes } from '@espace-student/routes';
import { useQuery, useRoute } from '@shared/hooks';
import { DataListType } from '@shared/types';
import { CancellationsType } from '@common/types';
import { SizeEnum } from '@shared/enums';

const tabs = [
    { name: 'Tous', id: '' },
    { name: 'Justifié', id: '1' },
    { name: 'Non Justifié', id: '0' },
];
const params = useRoute<{ tab: string }>(false);
const cancellationsQuery = useQuery<CancellationsType[]>({
    url: route(routes.api.cancellations.index),
    params: { is_justified: params.tab || tabs[0].id },
    mounted: true,
});
const selectedTab = ref(params.tab || tabs[0].id);
const selected = ref<CancellationsType | null>(null);

const onAction = (item: CancellationsType) => {
    selected.value = item;
};
const onTabChange = (tab: string) => {
    params.set({ is_justified: tab, page: 1 });
    cancellationsQuery.params.is_justified = tab;
    cancellationsQuery.fetch();
};
</script>

<template>
    <PageMobile title="Annulations des reservations" :width="SizeEnum.MD">
        <template #sticky>
            <TabSwitch v-model="selectedTab" :items="tabs" light size="lg" full @change="onTabChange" />
        </template>
        <DataView :query="cancellationsQuery" class="pt-5">
            <ul role="list" class="space-y-5 relative">
                <li :class="['bottom-20 absolute left-0 top-3 flex w-6 justify-center last']">
                    <div class="w-px bg-gray-300" />
                </li>
                <CancellationItem v-for="(item, date) in cancellationsQuery.data" :key="date" :item="item" @click="onAction(item)" />
            </ul>
        </DataView>
        <CancelDetailsDrawer :item="selected" @close="selected = null" />
    </PageMobile>
</template>
