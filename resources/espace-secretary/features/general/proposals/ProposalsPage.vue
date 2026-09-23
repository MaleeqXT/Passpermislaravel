<script setup lang="ts">
import { reactive, watch } from 'vue';
import { useQuery, useRoute } from '@shared/hooks';
import { Card, Filters, Page } from '@shared/components';
import { dateFormat } from '@shared/utils';
import { routes } from '@espace-secretary/routes';
import { ProposalLessonStatus, ProposalStatusEnum, TabAll } from '@common/enums';
import { DataView, ListMonitorsDialog } from '@common/components';
import { ProposalItem, ProposalFormDrawer } from './partials';
import type { ProposalType, UserType } from '@common/types';
type StateType = {
    selected: ProposalType | null;
};
const params = useRoute<{ status: ProposalStatusEnum; sort: string }>(false);

const state = reactive<StateType>({
    selected: null,
});
const proposalQuery = useQuery<Record<string, ProposalType[]>>({
    url: route(routes.api.proposals.index),
    params: params.getAll(),
    mounted: true,
});

const onChangeTab = (status: ProposalStatusEnum) => {
    proposalQuery.params.status = status;
    params.set({ status });
};
const onChangeSort = (sort: string) => {
    proposalQuery.params.sort = sort;
    params.set({ sort });
};

const onMonitorsChange = (user: UserType) => {
    proposalQuery.params.monitor_id = user?.monitor?.id || '';
};
watch(proposalQuery.params, () => {
    proposalQuery.fetch();
});
</script>

<template>
    <Page width="lg" title="Liste des propositions">
        <Card block>
            <Filters :tabs="[TabAll, ...Object.values(ProposalLessonStatus)]" api @change:tab="onChangeTab" @change:sort="onChangeSort">
                <ListMonitorsDialog @change="onMonitorsChange" />
            </Filters>
        </Card>
        <DataView :query="proposalQuery" :empty="Object.keys(proposalQuery.data).length">
            <ul class="relative pl-8 overflow-clip flex flex-col gap-5">
                <li class="absolute left-3 bg-gray-300 top-2 bottom-16 w-px"></li>
                <li v-for="(group, date) in proposalQuery.data" :key="date" class="relative z-1">
                    <span
                        class="size-2 rounded-full bg-gray-100 ring-8 ring-gray-100 border border-gray-400 absolute -left-[23px] top-1.5 z-1"
                    ></span>
                    <p class="text-md font-bold mb-1 flex justify-between">
                        <span> {{ dateFormat(date, 'full') }} </span>
                    </p>
                    <ul class="flex flex-col gap-3 mt-4">
                        <ProposalItem v-for="item in group" :key="item.id" :item="item" @click="state.selected = item" />
                    </ul>
                </li>
            </ul>
        </DataView>

        <ProposalFormDrawer :item="state.selected" @close="state.selected = null" />
    </Page>
</template>
