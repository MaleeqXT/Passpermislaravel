<script setup lang="ts">
import { OrderFulfilledIcon, OrderRepeatIcon, OrderUnfulfilledIcon, XSmallIcon } from '@adersolutions/icons';
import { CancelStatus, CancelStatusEnum } from '@common/enums';
import { CancellationsType } from '@common/types';
import { dateFormat } from '@shared/utils';
import { computed } from 'vue';

type PropsType = {
    item: CancellationsType;
};

const props = defineProps<PropsType>();
const Icon = computed(() => {
    switch (props.item.status) {
        case CancelStatusEnum.SUCCESS_CANCELED:
            return OrderFulfilledIcon;
        case CancelStatusEnum.REFUSED:
            return OrderUnfulfilledIcon;
        default:
            return OrderRepeatIcon;
    }
});
</script>

<template>
    <li class="relative flex gap-x-3">
        <div
            :class="[
                'relative flex size-6 flex-none items-center justify-center  ring-4 mt-4 ring-gray-100 rounded-full',
                'status-' + CancelStatus[item.status].class,
            ]"
        >
            <Icon class="size-6 text-" aria-hidden="true" />
        </div>
        <div class="flex-auto">
            <p class="mb-px text-xs flex justify-between">
                <span>{{ dateFormat(item?.created_at, 'letter') }}</span>
                <span>{{ dateFormat(item?.created_at, 'time') }}</span>
            </p>
            <dl class="flex-auto box bg-white overflow-clip divide-y">
                <dd
                    :class="[
                        'flex justify-between gap-2 font-semibold text-2xs/5 py-px px-2 border-none',
                        'status-' + CancelStatus[item.status].class,
                    ]"
                >
                    {{ CancelStatus[item.status].desc }}
                </dd>
                <dd class="text-xs px-2 py-1">
                    <span class="font-light opacity-70 text-xs">Candidat: </span>
                    <p>{{ item.training?.student?.user?.name }}</p>
                </dd>
                <dd class="text-xs px-2 py-1">
                    <span class="font-light opacity-70 text-xs">Raison: </span>
                    <p>{{ item.training?.cancellation?.comment }}</p>
                </dd>
            </dl>
        </div>
    </li>
</template>
