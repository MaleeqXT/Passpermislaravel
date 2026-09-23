<script setup lang="ts">
import moment from 'moment-timezone';
import { reactive } from 'vue';
import { useRoute, useQuery, useStorage } from '@shared/hooks';
import { PageMobile, WeeklySchedule } from '@common/components';
import { ScheduleReservationSection } from './partials';
import { AdjustIcon } from '@adersolutions/icons';
import { routes } from '@espace-student/routes';
import { SizeEnum } from '@shared/enums';
import { useStudentSpace } from '@espace-student/stores';
import { currentDate, parsedDates } from '@shared/utils';
import { watch } from 'vue';
import { ReservationType } from '@common/types';

const params = useRoute<{ date: string }>();
const { locations } = useStudentSpace();
// const [filter] = useStorage(KEY_SESSION_FILTER, null);
type RQType = { date_1: string; date_2: string; lieu_id?: string | null; all?: boolean };

const query = useQuery<Record<string, ReservationType[]>, RQType>({
    url: route(routes.api.reservations.get),
    params: {
        ...parsedDates(params.date || currentDate),
        lieu_id: locations.place || null,
    },
    mounted: true,
});

// Fetch previous week's reservations to allow an increased limit when previous week is empty
const prevQuery = useQuery<Record<string, ReservationType[]>, RQType>({
    url: route(routes.api.reservations.get),
    params: {
        ...parsedDates(moment((params.date || currentDate)).subtract(7, 'days').format('YYYY-MM-DD')),
        lieu_id: locations.place || null,
    },
    mounted: true,
});
// Fetch two-weeks-ago reservations to enforce consecutive-week blocking
const prevPrevQuery = useQuery<Record<string, ReservationType[]>, RQType>({
    url: route(routes.api.reservations.get),
    params: {
        ...parsedDates(moment((params.date || currentDate)).subtract(14, 'days').format('YYYY-MM-DD')),
        lieu_id: locations.place || null,
    },
    mounted: true,
});
const state = reactive({
    showFilter: false,
    date: query.params.date_1 || currentDate,
});
const onChangeWeekDate = ({ params }: { params: RQType }) => {
    query.params.date_1 = params.date_1;
    query.params.date_2 = params.date_2;
    query.fetch();

    // Update previous week query to the week before the selected week
    const prevStart = moment(params.date_1).subtract(7, 'days').format('YYYY-MM-DD');
    prevQuery.params = {
        ...parsedDates(prevStart),
        lieu_id: locations.place || null,
    };
    prevQuery.fetch();
    // Update two-weeks-ago query
    const prevPrevStart = moment(prevStart).subtract(7, 'days').format('YYYY-MM-DD');
    prevPrevQuery.params = {
        ...parsedDates(prevPrevStart),
        lieu_id: locations.place || null,
    };
    prevPrevQuery.fetch();
};
watch(locations, ({ place, show }) => {
    if (place && !show) {
        query.params.lieu_id = place || null;
        query.fetch();
    }
});
watch(locations, ({ place }) => {
    // keep prevQuery and prevPrevQuery in sync with location changes
    if (place) {
        prevQuery.params.lieu_id = place || null;
        prevQuery.fetch();
        prevPrevQuery.params.lieu_id = place || null;
        prevPrevQuery.fetch();
    }
});
watch(locations, ({ place }) => {
    // keep prevQuery in sync with location changes
    if (place) {
        prevQuery.params.lieu_id = place || null;
        prevQuery.fetch();
    }
});
</script>

<template>
    <PageMobile title="Votre calendar" :subtitle="moment(state.date).format('MMMM YYYY')" :width="SizeEnum.MD" class-wrapper="dd">
        <template #nav>
            <button class="btn-header btn-hover" @click="locations.open()">
                <AdjustIcon class="w-6" />
            </button>
        </template>
        <template #header>
            <div class="px-2">
                <WeeklySchedule v-model="params.date" api @change="onChangeWeekDate" />
            </div>
        </template>
        <ScheduleReservationSection :query="query" :date="state.date" :prev-query="prevQuery" :prev-prev-query="prevPrevQuery" />
    </PageMobile>
</template>
