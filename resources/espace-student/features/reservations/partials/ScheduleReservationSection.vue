<script setup lang="ts">
import { reactive, computed } from 'vue';
import { ScheduleSection, ScheduleItem } from '@common/components';
import type { ReservationType } from '@common/types';
import type { UseQueryType } from '@shared/hooks';
// import AvailableReservationDrawer from './AvailableReservationDrawer.vue';
import ReservationItem from './ReservationItem.vue';
import { ReservationDetailsDrawer } from '@espace-student/components';
import { isTimeOverlapping } from '@shared/utils';

type StateType = {
    selected: ReservationType | null;
};

type PropsType = {
    date: string;
    query: UseQueryType<Record<string, ReservationType[]>>;
    prevQuery?: UseQueryType<Record<string, ReservationType[]>>;
    prevPrevQuery?: UseQueryType<Record<string, ReservationType[]>>;
};

const props = defineProps<PropsType>();
const reservations = computed(() => props.query.data as unknown as { list: ReservationType[]; availables: ReservationType[] });
// Previous week reservations (if provided)
const prevWeekReservations = computed(() => props.prevQuery?.data as unknown as Record<string, ReservationType[]> | null);
// Two-weeks-ago reservations (if provided)
const prevPrevWeekReservations = computed(() => props.prevPrevQuery?.data as unknown as Record<string, ReservationType[]> | null);
const state = reactive<StateType>({
    selected: null,
});

// Extract student's confirmed (training) reservations for the week
const studentWeeklyReservations = computed(() => {
    const allData = props.query.data;
    const weeklyReservations: ReservationType[] = [];

    if (allData && typeof allData === 'object') {
        Object.values(allData).forEach((dayReservations: any) => {
            if (Array.isArray(dayReservations)) {
                dayReservations.forEach((reservation: ReservationType) => {
                    // Only include confirmed reservations (with training)
                    if (reservation.training) {
                        weeklyReservations.push(reservation);
                    }
                });
            }
        });
    }

    return weeklyReservations;
});

// Extract student's confirmed (training) reservations for the previous week
const studentPrevWeeklyReservations = computed(() => {
    const allData = prevWeekReservations.value;
    const weeklyReservations: ReservationType[] = [];
    if (allData && typeof allData === 'object') {
        Object.values(allData).forEach((dayReservations: any) => {
            if (Array.isArray(dayReservations)) {
                dayReservations.forEach((reservation: ReservationType) => {
                    if (reservation.training) {
                        weeklyReservations.push(reservation);
                    }
                });
            }
        });
    }
    return weeklyReservations;
});

// Extract student's confirmed (training) reservations for the week before the previous week
const studentPrevPrevWeeklyReservations = computed(() => {
    const allData = prevPrevWeekReservations.value;
    const weeklyReservations: ReservationType[] = [];
    if (allData && typeof allData === 'object') {
        Object.values(allData).forEach((dayReservations: any) => {
            if (Array.isArray(dayReservations)) {
                dayReservations.forEach((reservation: ReservationType) => {
                    if (reservation.training) {
                        weeklyReservations.push(reservation);
                    }
                });
            }
        });
    }
    return weeklyReservations;
});

const onShowDetails = (item: ReservationType) => {
    state.selected = item;
};

const onClose = (refresh: boolean) => {
    state.selected = null;
    if (refresh) {
        props.query.fetch();
    }
};
const getUniquePlaces = (item: ReservationType) => {
    const uniqueLieux = new Set(item?.lieux?.map((v) => v.name));
    return Array.from(uniqueLieux).join(' ou ');
};
</script>

<template>
    <div class="lg:bg-white rounded-b-xl w-full">
        <ScheduleSection v-slot="{ count, date }" class="relative" desktop :loading="query?.fetching" :attachable="false">
            <ScheduleItem v-for="item in query.data[date]" :key="item.id" :count="count" :item="item">
                <ReservationItem :item="item" :weekly-reservations="studentWeeklyReservations" :prev-weekly-reservations="studentPrevWeeklyReservations" :prev-prev-weekly-reservations="studentPrevPrevWeeklyReservations" @select="onShowDetails" :places="!item.training ? getUniquePlaces(item) : ''" />
            </ScheduleItem>
        </ScheduleSection>
        <!-- <AvailableReservationDrawer :state="state" @close="onClose" /> -->
        <ReservationDetailsDrawer :item="state.selected" @close="onClose" @refresh="query.fetch()" />
    </div>
</template>
