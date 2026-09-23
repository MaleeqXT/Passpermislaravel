<script setup lang="ts">
import { CalendarTimeIcon } from '@adersolutions/icons';
import { ActionsBottomBar, SelectLocationDialog, ScheduleItem, ScheduleSection } from '@common/components';
import { useMonitorSpace, useReservationDetails } from '@espace-monitor/stores';
import { ReservationDetailsDrawer, ListStudentDrawer } from '@espace-monitor/components';
import LessonItem from './LessonItem.vue';
import { useAvailability, useProposals } from '../ReservationsPage';
import { ReservationType } from '@common/types';
import { UseQueryType } from '@shared/hooks';
import ProposalDetailsDrawer from './ProposalDetailsDrawer.vue';
import ProposalConfirmationDrawer from './ProposalConfirmationDrawer.vue';
import { Thumb } from '@shared/components';
import { SizeEnum } from '@shared/enums';
import { getFilePath } from '@shared/utils';

type PropsType = {
    reservations: UseQueryType<Record<string, ReservationType[]>>;
    day: string;
};
defineEmits(['delete:availability']);
const props = defineProps<PropsType>();

const space = useMonitorSpace();
const details = useReservationDetails();
const proposals = useProposals(props.reservations);
const availability = useAvailability(props.reservations);

const onAddAvailability = (res: ReservationType, remove = false) => {
    availability.form.data = remove
        ? availability.form.data.filter((v) => !(v.date_hour === res.date_hour && v.date === res.date))
        : [...availability.form.data, res];
};
</script>
<template>
    <div class="w-full flex-1 flex flex-col">
        <ScheduleSection
            v-slot="{ count, date }"
            class="flex flex-auto max-w-screen-xl mx-auto rounded-b-2xl w-full"
            desktop
            :loading="reservations.fetching || proposals.query.fetching"
            :attachable="!space.state.student"
            @action:empty="onAddAvailability"
        >
            <ScheduleItem v-for="item in reservations.data[date]" :key="item.id" :count="count" :item="item">
                <LessonItem :item="item" @select="details.open" />
            </ScheduleItem>
            <ScheduleItem
                v-for="(item, idx) in availability.data(date)"
                :key="idx"
                :count="count"
                :item="item"
                @click="onAddAvailability(item as ReservationType, true)"
            >
                <div
                    class="bg-gradient-to-tr from-green-100 to-emerald-200 shadow-sm text-dark flex-center md:flex-col h-full rounded-lg p-1"
                >
                    <CalendarTimeIcon class="w-14 h-12 md:w-full md:h-8 md:p-1 p-3 bg-green-500 text-white rounded-lg" />
                    <p class="text-xs text-center flex-1 md:pt-5">{{ item.start_at }} - {{ item.end_at }}</p>
                </div>
            </ScheduleItem>
            <ScheduleItem v-for="(item, idx) in proposals.data(date)" :key="idx" :count="count" :item="item.reservation">
                <div
                    class="bg-gradient-to-tr h-full flex-1 rounded-lg flex md:flex-col gap-x-1 gap-y-1 p-1 from-blue-50 via-sky-100 to-blue-50 text-sky-700"
                    @click="space.proposals.selected = item"
                >
                    <div :class="['flex  gap-1 drop-shadow']">
                        <Thumb class="md:hiden" :src="getFilePath(item.student?.user)" :size="SizeEnum.MD" />
                        <p class="text-xs flex-1">
                            Une séance a été proposée pour condidat <b>{{ item.student?.user?.name }}</b> à
                            <b> {{ item.reservation.lieu?.name }}</b>
                        </p>
                    </div>
                </div>
            </ScheduleItem>
        </ScheduleSection>
        <ActionsBottomBar
            :show="!!availability.form.data?.length"
            :actions="[
                {
                    label: 'Effacer',
                    variant: 'danger',
                    onAction: () => (availability.form.data = []),
                },

                {
                    label: `Diponible pour  (${availability.form.data?.length}) seance`,
                    variant: 'primary',
                    full: true,
                    onAction: availability.togglePlaces,
                },
            ]"
        />
        <ActionsBottomBar
            :show="!!space.state.student"
            :actions="[
                {
                    label: 'Effacer',
                    variant: 'danger',
                    onAction: space.proposals.close,
                },

                {
                    label: `Proposez (${space.proposals.data.length}) Séance`,
                    variant: 'primary',
                    disabled: !space.proposals.data.length,
                    full: true,
                    onAction: () => (space.proposals.showConfirmation = true),
                },
            ]"
        />
        <SelectLocationDialog
            :show="availability.state.showPlaces"
            class-name="!p-0"
            custom-class="modal-mobile max-h-[calc(100%-3rem)] md:max-h-[calc(100vh-2rem)] h-fit"
            :processing="availability.form.processing || reservations.fetching"
            @close="availability.togglePlaces"
            @change="availability.save"
        />

        <ReservationDetailsDrawer @delete:availability="$emit('delete:availability', $event)" @refresh="reservations.fetch()" />
        <ListStudentDrawer />
        <ProposalDetailsDrawer @refresh="proposals.fetch()" />
        <ProposalConfirmationDrawer @refresh="proposals.fetch()" />
    </div>
</template>
