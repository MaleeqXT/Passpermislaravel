<script setup lang="ts">
import { CheckIcon } from '@adersolutions/icons';
import { dateFormat } from '@shared/utils';
import type { ReservationType } from '@common/types';
type PropsType = {
    item: ReservationType;
    isPassed?: boolean;
};
defineProps<PropsType>();
</script>
<template>
    <li :class="[' w-full box flex gap-1 flex-col py-1 px-3  text-md', isPassed ? 'bg-slate-200 bg-empty' : 'bg-white']">
        <p class="flex justify-between flex-1">
            <b class="capitalize">{{ dateFormat(item.date, 'full') }}</b>
            <span class="text-sm">{{ item.start_at }} {{ item.end_at }}</span>
        </p>

        <div>
            {{ item.training?.offer?.name }}
        </div>
    </li>
</template>
