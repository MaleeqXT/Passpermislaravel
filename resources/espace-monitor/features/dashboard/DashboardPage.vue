<script setup lang="ts">
import moment from 'moment-timezone';
import { reactive, computed, watch } from 'vue';
import { routes } from '@espace-monitor/routes';
import { ReservationDetailsDrawer } from '@espace-monitor/components';
import { MonthlyCountSchedule, PageMobile, WeekChanger } from '@common/components';
import { useMonthlySchedule, useReservationDetails } from '@espace-monitor/stores';
import { ScheduledReservationsBlock } from './partials';
import { CalendarIcon } from '@adersolutions/icons';
import { useQuery, useRoute } from '@shared/hooks';
import { SizeEnum } from '@shared/enums';
import { currentDate, parsedDates } from '@shared/utils';

const monthlySchedule = useMonthlySchedule();
const details = useReservationDetails();
const params = useRoute(false);

const reservations = useQuery({
    url: route(routes.api.reservations.schedule),
});

const state = reactive({
    date: params.date || currentDate,
    showMonthSchedule: false,
    week: {
        start: params.date_1 || parsedDates().date_1,
        end: params.date_2 || parsedDates().date_2,
    },
});
const getDates = computed(() => ({
    date_1: state.week.start || moment().startOf('isoWeek').format('YYYY-MM-DD'),
    date_2: state.week.end || moment().startOf('isoWeek').format('YYYY-MM-DD'),
}));
watch(getDates, (dates) => {
    reservations.params = dates;
    params.set(dates);
    reservations.fetch();
});

const count = computed(() => reservations.data[currentDate]?.length);

// const navigation = [
//     {
//         label: 'Proposer une séance',
//         href: route(routes.students.index),
//     },
//     {
//         label: 'Ajouter une disponibilité',
//         href: route(routes.reservations.index),
//     },
// ];
const onDateChange = ({ params }: { params: Record<string, any> }) => {
    reservations.params = params;
    reservations.fetch();
};
const onChangeWeek2 = ({ start, end }: { start: string; end: string }) => {
    reservations.params = { date_1: start, date_2: end };
    reservations.fetch();
};
</script>

<template>
    <PageMobile :title="moment(state.week.start).format('MMMM YYYY')" profile :width="SizeEnum.SM">
        <template #header>
            <h2 v-cloak class="text-2xl font-bold px-3 pt-2 pb-5 md:hidden">
                <span v-if="count">
                    Vous avez {{ count }} seance <br />
                    ce jour
                </span>
                <span v-else> Non seance ce jour </span>
            </h2>
        </template>
        <!-- <template #nav>
            <ActionSheet :actions="navigation">
                <PlusCircleIcon class="active:scale-95 active:opacity-70 t-200 btn-header" />
            </ActionSheet>
        </template> -->
        <template #nav>
            <button class="btn btn-header" @click="monthlySchedule.open">
                <CalendarIcon class="w-5" />
            </button>
        </template>
        <template #sticky>
            <WeekChanger v-model="state.week" mounted @open:schedule="monthlySchedule.open" />
        </template>
        <ScheduledReservationsBlock :reservations="reservations" />
        <ReservationDetailsDrawer @close="details.close" />
        <MonthlyCountSchedule
            v-model:week="state.week"
            range
            :loading="monthlySchedule.eventsQuery.fetching"
            :events="monthlySchedule.eventsQuery.data"
            :show="monthlySchedule.show"
            @close="monthlySchedule.close()"
            @refetch="onDateChange"
            @change:week="onChangeWeek2"
        />
    </PageMobile>
</template>
