<script setup lang="ts">
import { reactive, onMounted } from 'vue';
import { useRoute } from '@shared/hooks';
import { DataView, PageMobile } from '@common/components';
import { AffiliateIcon, NotificationIcon } from '@adersolutions/icons';
import { useNotifications } from '@espace-student/stores';
import { EmptyState, Spinner, Thumb } from '@shared/components';
import { ProposalItem, ProposalDetailsDrawer } from './partials';
import { SizeEnum } from '@shared/enums';
import { dateFormat, getFilePath, getName } from '@shared/utils';
import { computed } from 'vue';

const params = useRoute();
const { proposals, count } = useNotifications();
const tabs = [
    { name: 'Tous', id: '' },
    { name: 'Autres', id: '1' },
];

const state = reactive({
    tab: params.tab || tabs[0].id,
    selected: null,
});

const onRefetch = () => {
    count.fetch();
    proposals.fetch();
};

onMounted(() => {
    onRefetch();
});
</script>

<template>
    <PageMobile title="Notifications" :width="SizeEnum.MD">
        <DataView :query="proposals" :empty="Object.keys(proposals.data).length" :key="Object.keys(proposals.data).length">
            <template #empty>
                <p class="text-gray-500 text-center mt-4">Aucune séance proposée pour le moment.</p>
            </template>
            <ul class="relative pl-8 overflow-clip flex flex-col gap-5 mt-6">
                <li v-if="Object.keys(proposals.data).length" class="absolute left-3 bg-gray-300 inset-y-2 bottom-14 w-px"></li>
                <li v-for="(group, date) in proposals.data" :key="date" class="relative z-1">
                    <span
                        class="size-2 rounded-full bg-gray-100 ring-8 ring-gray-100 border border-gray-400 absolute -left-[23px] top-1.5 z-1"
                    ></span>
                    <p class="text-md font-bold mb-1 flex justify-between">
                        <span>Le {{ dateFormat(date, 'full') }} </span>
                    </p>
                    <ul class="flex flex-col gap-3 mt-4">
                        <ProposalItem v-for="item in group" :item="item" :key="item.id" @click="state.selected = item" />
                    </ul>
                </li>
            </ul>
        </DataView>
        <!-- <EmptyState v-if="!Object.keys(proposals).length" as="li" heading="Aucune séance proposée" class="-ml-8" /> -->
        <!-- <Spinner v-if="propositions.fetching" class="w-6 mx-auto my-10" />
        <ul v-else-if="propositions.data?.length" class="p-3 divide-y">
            <ProposalItem v-for="item in propositions.data" :key="item.id" :item="item" @click="state.item = item" />
        </ul>
        <EmptyState
            v-else
            :image="NotificationIcon"
            class="py-10 min-h-[70vh] flex flex-col justify-center gap-2"
            heading="Vous n'avez pas de notification"
        >
            <p class="text-center text-gray-700 max-w-md mx-auto text-xs">La liste des notifications est vide</p>
        </EmptyState> -->
        <ProposalDetailsDrawer :item="state.selected" @close="state.selected = null" @refetch="onRefetch" />
    </PageMobile>
</template>
