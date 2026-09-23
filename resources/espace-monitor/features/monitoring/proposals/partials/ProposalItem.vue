<script setup lang="ts">
import { Thumb } from '@shared/components';
import { SizeEnum } from '@shared/enums';
import { ProposalType } from '@common/types';
import { dateFormat, getFilePath } from '@shared/utils';
import { computed } from 'vue';
import { AffiliateIcon } from '@adersolutions/icons';
import { ProposalLessonStatus } from '@common/enums';

type PropsType = {
    item: ProposalType;
};

const props = defineProps<PropsType>();
const status = computed(() => ProposalLessonStatus[props.item.status]);
</script>

<template>
    <li class="relative to-white text-dark rounded-lg">
        <component
            :is="status.icon"
            :class="['size-6  rounded-full ring-4 ring-gray-100 absolute -left-[31.5px] top-0 z-1 p-0.5', 'status-' + status.class]"
        />
        <p :class="['text-2xs font-medium flex justify-between  px-2 rounded-t-lg', 'status-' + status.class]">
            <!-- <span> {{ dateFormat(item.reservation?.date, 'fromNow') }}</span>
            <span> {{ item.reservation.start_at }} - {{ item.reservation.end_at }} </span> -->
            {{ ProposalLessonStatus[item.status].desc }}
        </p>
        <div :class="[' flex-1 rounded-b-lg flex gap-1 p-1 bg-rainbow rainbow-opacity-30 bg-white']">
            <Thumb :src="getFilePath(item.student?.user)" :size="SizeEnum.MD" />
            <p class="text-xs flex-1">
                Vous avez proposé une séance pour le candidat
                <br />
                <b>{{ item.student?.user?.name }}</b> à
                <b> {{ item.reservation.lieu?.name }}</b>
            </p>
        </div>
    </li>
</template>
