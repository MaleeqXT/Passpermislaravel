<script setup lang="ts">
import { NotificationIcon } from '@adersolutions/icons';
import { ProposalType } from '@common/types';
import { Thumb } from '@shared/components';
import { SizeEnum } from '@shared/enums';
import { dateFormat, getFilePath, getName } from '@shared/utils';
type PropsType = {
    item: ProposalType;
};
defineProps<PropsType>();
</script>

<template>
    <li class="relative rounded-lg">
        <NotificationIcon class="size-6 text-white bg-dark rounded-full ring-4 ring-gray-100 absolute -left-[31.5px] top-0 z-1 p-0.5" />
        <div :class="['bg-blue-100 flex-1 rounded-lg flex gap-2 items-center p-1 bg-rainbow rainbow-opacity-30 box']">
            <Thumb :src="getFilePath(item?.reservation?.monitor?.user)" :size="SizeEnum.MD" />
            <p class="text-xs/5 flex-1">
                Vous avez une séance programmée avec <br />
                <b>{{ getName(item?.reservation?.monitor?.user) }}</b> à <b>{{ item.reservation?.lieu?.name }}</b> de
                <b>{{ item.reservation?.start_at }}</b> à <b>{{ item.reservation?.end_at }}</b>
            </p>
        </div>
    </li>
</template>
