<template>
    <li
        :class="[
            'flex gap-1 items-center rounded-lg p-0.5 hover:scale-105 t-3 bg-rainbow rainbow-opacity-30 bg-custom  drop-shadow w-full',
            bgStyle.className,
        ]"
        :style="bgStyle.style"
        @click.prevent="$emit('select')"
    >
        <Thumb :src="getFilePath(item?.monitor?.user)" :size="SizeEnum['2XS']" class="!rounded-[7px]" />
        <span class="flex-1 text-left truncate font-medium">
            {{ item?.training?.student?.user?.name ?? item?.monitor?.user?.name }}
        </span>
        <time :datetime="item.start_at" class="ml-1 hidden flex-none xl:block pr-0.5">
            {{ item.start_at?.slice(0, 5) }}
        </time>
    </li>
</template>

<script setup lang="ts">
import { Badge, Popup, Thumb } from '@shared/components';
import { SizeEnum } from '@shared/enums';
import { dateFormat, getFilePath } from '@shared/utils';
import { computed } from 'vue';
import { getColor, useReservations } from '../../ReservationsPage';
import type { ReservationType } from '@common/types';

type PropsType = {
    item: ReservationType;
    // count: number;
};

defineEmits(['select']);
const props = withDefaults(defineProps<PropsType>(), {
    // count: 0,
});

// const student = computed(() => props.item?.training?.student || null);
const bgStyle = computed(() => getColor(props.item));
</script>
