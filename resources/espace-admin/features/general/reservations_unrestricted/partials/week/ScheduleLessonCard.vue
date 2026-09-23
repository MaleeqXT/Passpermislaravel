<script setup lang="ts">
import { computed, reactive, watch } from 'vue';
import { ScheduleItem } from '@common/components';
import { Thumb, Spinner } from '@shared/components';
import { SizeEnum } from '@shared/enums';
import { getFilePath, getName, dateFormat } from '@shared/utils';
import { useReservations, getColor } from '../../ReservationsPage';
import type { MonitorType, ReservationType } from '@common/types';

type PropsType = {
    item: ReservationType;
    uniqueMonitors: { count: number; list: MonitorType[] };
    count: number;
    index: number | string;
};
const props = withDefaults(defineProps<PropsType>(), {
    item: () => ({} as ReservationType),
    uniqueMonitors: () => ({ count: 0, list: [] }),
    count: 0,
    index: 0,
});
const getOrder = computed(() => props.uniqueMonitors.list.findIndex((monitor) => monitor.id === props.item?.monitor_id) + 1);
const { state } = useReservations();

const student = computed(() => props.item?.training?.student || null);
const bgStyle = computed(() => getColor(props.item));

// ---------------- RESERVATION COMMENTS ----------------
const stateLocal = reactive({
    reservationComments: [] as any[],
    loadingComments: false,
});

const fetchReservationComments = async () => {
    if (!props.item?.id) return;

    stateLocal.loadingComments = true;
    try {
        const response = await fetch(`/reservations/${props.item.id}/comments`);
        const data = await response.json();
        stateLocal.reservationComments = data.data || [];
    } catch (error) {
        console.error('Error fetching comments:', error);
        stateLocal.reservationComments = [];
    } finally {
        stateLocal.loadingComments = false;
    }
};

watch(
    () => props.item?.id,
    (newId) => {
        if (newId) {
            fetchReservationComments();
        } else {
            stateLocal.reservationComments = [];
        }
    },
    { immediate: true }
);
</script>

<template>
    <ScheduleItem
        :count="count"
        :item="item"
        class="!p-1"
        :style="{
            width: 'calc(100% / ' + uniqueMonitors.count + ')',
            marginLeft: 'calc(100% / ' + uniqueMonitors.count + ' * ' + (getOrder - 1) + ')',
        }"
    >
        <div
            :style="bgStyle.style"
            :class="[
                bgStyle.className,
                'md:order-' + getOrder,
                'col-start-' + getOrder,
                'cursor-pointer active:scale-95 group size-full rounded-lg text-[8px] t-3 p-1 flex flex-col hover:scale-105 drop-shadow',
            ]"
            @click="state.selected = item"
        >
            <div class="relative z-1 flex-1 flex flex-col">
                <p class="font-semibold line-clamp-1">
                    {{ item?.training ? student?.user?.name : getName(item?.monitor?.user) + ' a Disponible ' }}
                </p>
                <time v-if="item?.start_at" :datetime="item?.start_at">
                    {{ item?.start_at.slice(0, 5) }} - {{ item.end_at?.slice(0, 5) }}
                </time>
                <p class="drop-shadow line-clamp-1">{{ item?.lieu?.name }}</p>

                <div v-if="stateLocal.reservationComments.length > 0" class="mt-2">
                    <div v-if="stateLocal.loadingComments" class="text-center py-2">
                        <Spinner class="w-4 h-4 mx-auto" />
                        <p class="text-xs text-gray-500 mt-1">Chargement des commentaires...</p>
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="(comment, index) in stateLocal.reservationComments"
                            :key="comment.id"
                            class="border-b pb-2 last:border-b-0"
                        >
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-[8px] text-gray-400">
                                    {{ dateFormat(comment.created_at, 'short') }}
                                </span>
                            </div>
                            <p class="text-[8px] text-gray-600 whitespace-pre-wrap bg-gray-50 p-2 rounded">
                                {{ comment.comment }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex -space-x-2 mt-auto pt-1">
                    <Thumb :src="getFilePath(item?.monitor?.user)" :size="SizeEnum.XS" class="ring-1 ring-custom border-green-100" />
                    <Thumb v-if="student" :src="getFilePath(student?.user)" :size="SizeEnum.XS" class="ring-1 ring-custom" />
                </div>
            </div>
        </div>
    </ScheduleItem>
</template>
