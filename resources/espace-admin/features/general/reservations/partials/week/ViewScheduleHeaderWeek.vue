<script setup lang="ts">
import moment from 'moment-timezone';
import { currentDate, getFilePath, getInitials } from '@shared/utils';
import { getReservationDate, useReservations } from '../../ReservationsPage';
import { listDays } from '../../ReservationsPage';
import { ChevronLeftIcon, ChevronRightIcon } from '@adersolutions/icons';
import type { MonitorType } from '@common/types';
// import { Thumb } from '@shared/components';
// import { SizeEnum } from '@shared/enums';
// import { ItemImage } from '@common/components';
// import { routes } from '@espace-admin/routes';

type PropsType = {
    uniqueMonitors: Record<string, { count: number; list: MonitorType[] }>;
};
defineProps<PropsType>();
// const schedule = useScheduleNavigate();
const { onDateChange } = useReservations();
</script>

<template>
    <div class="sticky top-0 z-30 flex-none bg-dark text-white bg-rainbow">
        <div class="h-rainbow"></div>
        <ul class="hidden grid-cols-7 divide-x divide-gray-400/30 text-sm leading-6 sm:grid relative z-1">
            <li class="col-end-1 w-16 flex-center">
                <ChevronLeftIcon class="w-9 h-9 cursor-pointer hover:bg-white/10 rounded-lg text-primary p-2" @click="onDateChange(-1)" />
            </li>
            <li v-for="(day, count) in listDays" :key="day" class="grid grid-cols-1 w-full text-center py-2">
                <span class="flex-center flex-col text-xl font-light">
                    <span class="text-xs opacity-70">{{ day.slice(0, 3) }}</span>
                    <span
                        :class="[
                            'flex  aspect-square items-center justify-center  font-bold',
                            currentDate === getReservationDate(count) ? 'rounded-full bg-primary ' : '',
                        ]"
                    >
                        {{ getReservationDate(count, 'DD') }}
                    </span>
                </span>
                <!-- <ul
                    v-if="uniqueMonitors[getReservationDate(count)]"
                    :class="'grid-cols-' + uniqueMonitors[getReservationDate(count)].count"
                    class="grid h-full text-center p-0.5 gap-0.5"
                >
                    <li
                        v-for="(monitor, idx) in uniqueMonitors[getReservationDate(count)].list"
                        :key="idx"
                        class="px-0.5 py-1 flex-center items-center gap-1 rounded-md btn-header text-ellipsis relative"
                    >
                        <ItemImage
                            :src="getFilePath(monitor?.user)"
                            :href="route(routes.users.monitors.edit, monitor.id)"
                            class="mx-auto"
                            size="w-6 h-6 "
                        />
                    </li>
                </ul> -->
            </li>

            <li class="col-end-9 w-16 flex-center">
                <ChevronRightIcon class="w-9 h-9 cursor-pointer hover:bg-white/10 rounded-lg text-primary p-2" @click="onDateChange(1)" />
            </li>
        </ul>

        <div class="h-rainbow"></div>
    </div>
</template>
