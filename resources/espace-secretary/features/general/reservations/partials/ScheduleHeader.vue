<script setup lang="ts">
import { UndoIcon } from '@adersolutions/icons';
import { AreaSelectionDialog, ListMonitorsDialog, ListStudentsDialog } from '@common/components';
import type { AreaType, PlaceType, ScheduleViewType, UserType } from '@common/types';
import { Button, TabSwitch } from '@shared/components';
import { STORAHE_KEY_RESERVATION, useReservations } from '../ReservationsPage';
import { useStorage } from '@shared/hooks';
import { nextTick } from 'vue';

const { filters, onDateChange, parsedFilters } = useReservations();
// const params = useRoute();

const eventView = (value: ScheduleViewType = 'week') => {
    filters.view = value;
    // setStorage({ ...storage.value, view: value });
    nextTick(() => {
        onDateChange();
    });
};
const onAvailabilityChange = (value: boolean) => {
    filters.disp = value;
    // setStorage({ ...storage.value, disp: value });
};
const onAreaChange = ({ area, place }: { area: AreaType; place: PlaceType }) => {
    filters.zone_id = area;
    filters.lieu_id = place;
};

const onMonitorsChange = (items: UserType[]) => {
    filters.monitor_id = items;
};
const onStudentChange = (items: UserType[]) => {
    filters.student_id = items;
};
const reset = () => {
    filters.zone_id = null;
    filters.lieu_id = null;
    filters.monitor_id = [];
    filters.student_id = [];
    filters.view = 'week';
    filters.disp = false;
    // setStorage({});
};
</script>
<template>
    <section class="flex items-center gap-2 py-4 lg:flex-none relative">
        <div class="h-rainbow absolute top-0 w-[calc(100%+4rem)] -left-8"></div>
        <AreaSelectionDialog
            :model-value="{
                area: filters.zone_id,
                place: filters.lieu_id,
            }"
            @change="onAreaChange"
        />
        <ListMonitorsDialog
            v-model="filters.monitor_id"
            multi
            :filters="{
                zone_id: filters.zone_id?.id,
                lieu_id: filters.lieu_id?.id,
            }"
        />

        <ListStudentsDialog multi :filters="{}" @change="onStudentChange" />

        <div class="flex-grow"></div>
        <TabSwitch
            v-model="filters.view"
            :items="[
                { name: 'Semaine', id: 'week' },
                { name: 'Mois', id: 'month' },
            ]"
            @change="eventView($event)"
        />
        <TabSwitch
            v-model="filters.disp"
            :items="[
                { name: 'Reservation', id: false },
                { name: 'Disponibilité', id: true },
            ]"
            @change="onAvailabilityChange"
        />
        <Button :icon="UndoIcon" variant="dark" tooltip="Effacer filter" @click="reset" />
    </section>
</template>
