<script setup lang="ts">
import { CheckSmallIcon, XSmallIcon } from '@adersolutions/icons';
import { CancelStatus } from '@common/enums';
import { ReservationType } from '@common/types';
import { Badge, Thumb } from '@shared/components';
import { SizeEnum } from '@shared/enums';
import { dateFormat, getFilePath } from '@shared/utils';
import moment from 'moment-timezone';
import { computed } from 'vue';

defineEmits(['open:menu']);

type PropsType = {
    item: ReservationType;
};

const props = defineProps<PropsType>();
const currentHour = moment().hours();
const currentDay = moment().date();
const state = computed(() => {
    const { end_at, datef, date_hour, hour, day } = props.item;
    const passed = moment().format('yyyy-MM-DD HH:mm') > datef + ' ' + end_at;
    const current = currentHour >= date_hour && currentHour < date_hour + hour && day === currentDay;
    return { passed, current };
});
</script>
<template>
    <div :class="['contents']">
        <!-- canceled reservation  -->
        <template v-if="item.training?.cancellation">
            <div class="relative flex size-6 bg-rose-700 flex-center rounded-full">
                <XSmallIcon class="size-6 text-white" aria-hidden="true" />
            </div>

            <dl class="flex-auto rounded-lg ring-1 ring-inset ring-gray-200 overflow-clip bg-rose-600/5">
                <dd class="flex justify-between gap-2 bg-rose-700 text-white font-semibold text-2xs/5 py-px px-2">
                    {{ CancelStatus[item.training.cancellation.status].desc }}
                </dd>
                <dd class="text-xs bg-dark text-white px-3 py-1.5">Raison: {{ item.training?.cancellation?.comment }}</dd>

                <dd class="px-3 py-2 text-xs/5 text-gray-500">
                    <span class="font-medium text-gray-900">{{ item.training?.offer?.name }}</span> avec Condidat
                    <span class="font-medium text-gray-900">{{ item.training?.student?.user?.name }}</span>
                </dd>
            </dl>
        </template>
        <!-- passed reservation -->
        <template v-else-if="state.passed">
            <div class="relative flex size-6 bg-primary flex-center rounded-full">
                <CheckSmallIcon class="size-6 text-white" aria-hidden="true" />
            </div>

            <dl class="flex-auto rounded-lg overflow-clip bg-gray-200">
                <dd class="flex justify-between gap-2 text-primary text-2xs py-1 bg-gray-300/20 px-3">
                    <span>La séance a été passée</span>
                    <time :datetime="item.date"> {{ item.start_at }} - {{ item.end_at }} </time>
                </dd>
                <dd class="text-xs text-gray-500 py-2 px-3">
                    <span class="font-medium">{{ item.training?.offer?.name }}</span> avec Condidat
                    <span class="font-medium">{{ item.training?.student?.user?.name }}</span>
                    <p class="flex-1 truncate">À {{ item.lieu?.name }} , {{ item.lieu?.zone?.name }}</p>
                </dd>
            </dl>
        </template>

        <!-- next reservation -->
        <template v-else>
            <div class="relative min-w-0 h-fit flex-none rounded-lg flex-center flex-col ring-4 ring-gray-100 -ml-[3.5px]">
                <Thumb :src="getFilePath(item.training?.student?.user)" :size="SizeEnum.XS" class="!rounded-full" />
            </div>

            <dl
                :class="[
                    'flex-auto rounded-lg ring-1 ring-inset ring-gray-200 overflow-clip',
                    state.current ? 'bg-primary/15' : 'bg-white',
                ]"
            >
                <dd v-if="state.current" class="text-center bg-primary text-white font-bold text-2xs/5 py-px px-2">En cours</dd>
                <dd
                    :class="[
                        ' text-xs/5 flex justify-between gap-3 py-1 px-2',
                        state.current ? 'bg-dark text-white' : 'bg-dark/5 text-gray-800',
                    ]"
                >
                    <p class="flex-1 truncate">
                        À
                        <span class="text-primary font-semibold"> {{ item.lieu?.name }} </span>
                        , {{ item.lieu?.zone?.name }}
                    </p>
                    <time :datetime="item.date" class="text-primary font-semibold"> {{ item.start_at }} - {{ item.end_at }} </time>
                </dd>
                <dd class="flex justify-between gap-x-4 p-2">
                    <div class="py-0.5 text-xs/5 text-gray-500">
                        <span class="font-medium text-gray-900">{{ item.training?.offer?.name }}</span> avec Condidat
                        <span class="font-medium text-gray-900">{{ item.training?.student?.user?.name }}</span>
                    </div>
                </dd>
            </dl>
        </template>
    </div>
</template>
