<script setup lang="ts">
import { reactive, onMounted } from 'vue';
import moment from 'moment-timezone';
import { useRoute, ParamsType, useQuery } from '@shared/hooks';
import { MonthlyCountSchedule, PageMobile, StudentStatsCard, WeeklySchedule } from '@common/components';
import { routes } from '@espace-monitor/routes';
import { ScheduleBlock, RecurringHoursModal } from './partials';
import { parsedDates, currentDate } from '@shared/utils';
import { useMonitorSpace, useMonthlySchedule } from '@espace-monitor/stores';
import { CalendarIcon, SettingsFilledIcon } from '@adersolutions/icons';
import type { ReservationType, StudentType } from '@common/types';

type PropsType = {
    student?: StudentType;
    // events: Record<string, { reserved: number; dispo: number }>;
};
// events
// const eventsQuery = useQuery<Record<string, { reserved: number; dispo: number }>>({
//     url: route(routes.api.reservations.events),
//     // params: filters,
//     // mounted: true,
//     dataType: {},
// });
const props = defineProps<PropsType>();
const params: ParamsType<{ date: string; w: string }> = useRoute(false);
const monthlySchedule = useMonthlySchedule();
const state = reactive({
    date: params.date || currentDate,
    showRecurringHoursModal: false,
});
const space = useMonitorSpace();
const reservationsQuery = useQuery({
    url: route(routes.api.reservations.schedule),
    params: parsedDates(params.date || currentDate),
    mounted: true,
});

const onDateChange = ({ params }: { params: Record<string, any> }) => {
    reservationsQuery.params = params;
    reservationsQuery.fetch();
};

const onDeleteAval = (item: ReservationType) => {
    reservationsQuery.data[item.datef] = reservationsQuery.data[item.datef].filter((v: ReservationType) => v.id !== item.id);
};

const onOpenCalendarOption = () => {
    state.showRecurringHoursModal = true;
};

const onSaveRecurringHours = (data: any) => {
    console.log('Save recurring hours:', data);
    // TODO: Implement API call to save recurring hours
};

onMounted(() => {
    space.state.student = props.student || null;
});
</script>

<template>
    <PageMobile
        :title="`Séances du ${moment(state.date).format('MMMM yyyy')}`"
        subtitle="Gérez votre Séance et disponibilité"
        class="relative z-10"
        profile
    >
        <template #nav>
            <button class="btn btn-header" @click="onOpenCalendarOption">
                <SettingsFilledIcon class="w-5" />
            </button>
            <button class="btn btn-header" @click="monthlySchedule.open">
                <CalendarIcon class="w-5" />
            </button>
        </template>
        <template #header>
            <StudentStatsCard v-if="space.state.student?.id" class="p-3" mounted :student="space.state.student" />
            <WeeklySchedule v-model="state.date" api @change="onDateChange" />
        </template>
        <ScheduleBlock :reservations="reservationsQuery" :day="state.date" @delete:availability="onDeleteAval" />

        <MonthlyCountSchedule
            v-model="state.date"
            :range="monthlySchedule.md"
            :loading="monthlySchedule.eventsQuery.fetching"
            :events="monthlySchedule.eventsQuery.data"
            :show="monthlySchedule.show"
            @close="monthlySchedule.close()"
            @refetch="onDateChange"
        />

        <RecurringHoursModal
            :show="state.showRecurringHoursModal"
            @close="state.showRecurringHoursModal = false"
            @refresh="reservationsQuery.fetch()"
        />
    </PageMobile>
</template>
