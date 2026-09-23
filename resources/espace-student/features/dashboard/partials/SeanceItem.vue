<script setup lang="ts">
import moment from 'moment-timezone';
import { CancelStatus, CancelStatusEnum } from '@common/enums';
import { CheckIcon, XSmallIcon } from '@adersolutions/icons';
import { dateFormat } from '@shared/utils';
import { computed } from 'vue';
import { Badge } from '@shared/components';
import { ReservationType } from '@common/types';

defineEmits(['select']);

type PropsType = {
    item: ReservationType;
    isPassed?: boolean;
};

const props = defineProps<PropsType>();

const cancellation = computed(() => {
    if (props.isPassed) return null;
    return props.item.training?.cancellation;
});

// const isTodaySession = (session) => {
//     if (props.isPassed) {
//         return false;
//     }
//     const isToday = session.datef === moment().format('yyyy-MM-DD');
//     if (!isToday) {
//         return false;
//     }
//     return moment().isBetween(moment(session.start_at, 'HH:mm'), moment(session.end_at, 'HH:mm'), 'hours', '[)');
// };
</script>

<template>
    <li :class="['contents']">
        <!-- passed reservation -->
        <template v-if="isPassed">
            <div class="relative flex size-6 flex-none items-center justify-center bg-gray-100">
                <CheckIcon class="size-6 text-gray-400 rounded-full" aria-hidden="true" />
            </div>

            <div class="flex-auto p-3 box bg-white">
                <div class="flex justify-between gap-x-4">
                    <div class="py-0.5 text-xs/5 text-gray-500">
                        <span class="font-medium text-gray-900">{{ item.training?.offer?.name }}</span> avec
                        <span class="font-medium text-gray-900">{{ item.monitor?.user?.first_name }}</span>
                    </div>
                    <time :datetime="item.date" class="flex-none py-0.5 text-xs/5 text-gray-500">
                        {{ item.start_at }} - {{ item.end_at }}
                    </time>
                </div>
                <p class="text-xs/5 text-gray-500">À {{ item.lieu?.name }}, {{ item.lieu?.zone?.name }}</p>
            </div>
        </template>

        <!-- canceled reservation  -->
        <template v-else-if="cancellation">
            <div class="relative flex size-6 bg-rose-700 flex-center rounded-full">
                <XSmallIcon class="size-6 text-white" aria-hidden="true" />
            </div>
            <dl class="flex-auto rounded-md ring-1 ring-inset ring-gray-200 overflow-clip bg-rose-600/5">
                <dd :class="['text-2xs/4 py-px px-2', 'status-' + CancelStatus[cancellation.status].class]">
                    <p>{{ dateFormat(item.training?.cancellation?.updated_at, 'fr-full') }}</p>
                    <b>{{ CancelStatus[cancellation.status].desc }}</b>
                </dd>
                <dd class="text-xs bg-dark text-white px-3 py-1.5">Raison: {{ item.training?.cancellation?.comment }}</dd>

                <dd class="px-3 py-2 text-xs/5 text-gray-500">
                    <span class="font-medium text-gray-900">{{ item.training?.offer?.name }}</span> avec
                    <span class="font-medium text-gray-900">{{ item.monitor?.user?.first_name }}</span>
                </dd>
            </dl>
        </template>
        <!-- next reservation -->
        <template v-else>
            <div
                class="relative size-7 min-w-0 flex-none rounded-full btn-header flex-center flex-col text-3xs/3 ring-4 ring-gray-100 -ml-0.5 uppercase"
            >
                <b class="text-sm">{{ item.day }}</b>

                {{ moment(item.date).format('ddd') }}
            </div>

            <div class="flex-auto bg-white p-3 box">
                <div class="flex justify-between gap-x-4">
                    <div class="py-0.5 text-xs/5 text-gray-500">
                        <span class="font-medium text-gray-900">{{ item.training?.offer?.name }}</span> avec
                        <span class="font-medium text-gray-900">{{ item.monitor?.user?.first_name }}</span>
                    </div>
                    <time :datetime="item.date" class="flex-none py-0.5 text-xs/5 text-gray-500">
                        {{ item.start_at }} - {{ item.end_at }}
                    </time>
                </div>
                <p class="text-xs/5 text-gray-500">À {{ item.lieu?.name }}, {{ item.lieu?.zone?.name }}</p>
            </div>
        </template>
    </li>
</template>
