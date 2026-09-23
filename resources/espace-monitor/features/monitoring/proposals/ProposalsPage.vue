<script setup lang="ts">
import { EmptyState, SearchField, Thumb } from '@shared/components';
import { PageMobile } from '@common/components';
import { SizeEnum } from '@shared/enums';
import { ProposalType } from '@common/types';
import { dateFormat, getFilePath } from '@shared/utils';
import { reactive } from 'vue';
import { useRoute } from '@shared/hooks';
import { ProposalDetailsDrawer } from '@espace-monitor/features/reservations/partials';
import { useMonitorSpace } from '@espace-monitor/stores';
import { AffiliateIcon } from '@adersolutions/icons';
import { ProposalLessonStatus } from '@common/enums';
import { ProposalItem } from './partials';

type PropsType = {
    proposals: Record<string, ProposalType[]>;
};

defineProps<PropsType>();
const params = useRoute<{ search: string }>();
const space = useMonitorSpace();
</script>

<template>
    <PageMobile title="Liste des séances proposées" :width="SizeEnum.XS">
        <template #sticky>
            <div class="p-1">
                <SearchField
                    :model-value="params.search"
                    class="bg-gray-300/30 rounded-xl !outline-none h-10"
                    placeholder="rechercher avec nom de condidat"
                    :loading="params.loading"
                    @change="params.set({ search: $event })"
                />
            </div>
        </template>
        <ul class="relative pl-8 overflow-clip flex flex-col gap-5 mt-6">
            <li v-if="Object.keys(proposals).length" class="absolute left-3 bg-gray-300 inset-y-2 w-px bottom-14"></li>
            <li v-for="(group, date) in proposals" :key="date" class="relative z-1">
                <span
                    class="size-2 rounded-full bg-gray-100 ring-8 ring-gray-100 border border-gray-400 absolute -left-[23px] top-1.5 z-1"
                ></span>
                <p class="text-md font-bold mb-1 flex justify-between">
                    <span> {{ dateFormat(date, 'full') }} </span>
                </p>
                <ul class="flex flex-col gap-3 mt-4">
                    <ProposalItem v-for="item in group" :key="item.id" :item="item" @click="space.proposals.selected = item" />
                </ul>
            </li>
            <EmptyState v-if="!Object.keys(proposals).length" as="li" heading="Aucune séance proposée" class="-ml-8" />
        </ul>
        <ProposalDetailsDrawer @refresh="params.reload()" />
    </PageMobile>
</template>
