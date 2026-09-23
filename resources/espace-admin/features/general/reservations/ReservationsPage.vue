<script setup lang="ts">
import { Card, DateField, DialogConfirm, EmptyState, Page, Spinner } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useMutation, useQuery } from '@shared/hooks';
import { ViewScheduleByMonth, ViewScheduleByWeek, ScheduleHeader, LessonMonitorDialog, ReservationFormDrawer } from './partials';
import { useReservations } from './ReservationsPage';
import { ArrowLeftIcon, ArrowRightIcon } from '@adersolutions/icons';
import { MonthlyCountSchedule } from '@common/components';
// import { computed } from 'vue';
// import moment from 'moment-timezone';
import { dateFormat } from '@shared/utils';

const { filters, query, state, activeMonth, parsedFilters, onDateChange } = useReservations();

const eventsQuery = useQuery<Record<string, { reserved: number; dispo: number }>>({
    url: route(routes.api.reservations.events),
    params: parsedFilters(filters),
    // mounted: true,
    dataType: {},
});
const form = useMutation();
const actions = [
    {
        label: 'Nouvelle réservation',
        variant: 'primary',
        icon: null,
        onAction: () => {
            state.selected = {};
            state.show = true;
        },
    },
];

const onDeleteLesson = () => {
    form.delete(route(routes.api.reservations.destroy, state.selected?.id)).then(() => {
        state.delete = false;
        state.selected = null;
        query.fetch();
    });
};
const onConfirmedUpdateToDispo = () => {
    form.put(route(routes.api.reservations.annulation, state.selected?.id)).then(() => {
        state.updateToDispo = false;
        state.selected = null;
        query.fetch();
    });
};
</script>
<template>
    <Page :actions="actions" :title="activeMonth().format('MMMM YYYY')" width="full">

        <ScheduleHeader />

        <Card class="overflow-hidden relative" block>
            <!-- <div class="v-rainbow"></div> -->
            <div class="v-rainbow after:!right-0 after:!left-auto contents"></div>
            <div class="lg:flex lg:h-full lg:flex-col">

                <div v-if="query.fetching" class="absolute z-900 inset-0 top-0 bg-white/70 flex-center backdrop-blur-sm">
                    <Spinner class="w-6" />
                </div>

                <EmptyState
                    v-if="!filters.monitor_id?.length"
                    class="py-10 min-h-[60vh] flex flex-col justify-center gap-2"
                    heading="Aucun Moniteur sélectionné"
                >
                    Explorez la liste des Moniteurs et choisissez celui qui convient le mieux à vos attentes.
                </EmptyState>

                <ViewScheduleByMonth v-else-if="filters.view === 'month'" :data="query.data" />
                <ViewScheduleByWeek v-else :data="query.data" :key="query.fetching ? '1' : '0'" />
            </div>
        </Card>
        <LessonMonitorDialog />
        <ReservationFormDrawer @refetch="query.fetch()" />
        <DialogConfirm :loading="form.processing" :show="!!state.delete" @close="state.delete = false" @confirm="onDeleteLesson">
            <p class="5">
                Etes-vous sûr que vous voulez supprimer cette seance <br />
                <b class="text-red-500">{{ state.selected?.monitor?.user?.name }}</b> le
                <b class="text-red-500">{{ state.selected ? dateFormat(state.selected.date) : '' }}</b>
                à
                <b class="text-red-500">{{ state.selected?.start_at?.slice(0, 5) }}</b>
                ?
            </p>
        </DialogConfirm>
        <DialogConfirm
            :loading="form.processing"
            :show="!!state.updateToDispo"
            @close="state.updateToDispo = false"
            @confirm="onConfirmedUpdateToDispo"
        >
            <div class="p-5">
                Etes-vous sûr que vous voulez annuler cette seance de <br />
                <b class="text-orange-500">{{ state.selected?.monitor?.user?.name }}</b> le
                <b class="text-orange-500">{{ state.selected ? dateFormat(state.selected.date) : '' }}</b>
                à
                <b class="text-orange-500">{{ state.selected?.start_at?.slice(0, 5) }}</b>
                ?
            </div>
        </DialogConfirm>
    </Page>
</template>
