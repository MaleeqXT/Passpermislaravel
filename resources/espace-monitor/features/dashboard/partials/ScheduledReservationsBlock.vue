<script setup lang="ts">
import { EmptyState } from '@shared/components';
import { CalendarIcon } from '@adersolutions/icons';
import { useReservationDetails } from '@espace-monitor/stores';
import Spinner from '@shared/components/feedbackIndicator/Spinner.vue';
import { dateFormat } from '@shared/utils';
import ScheduledReservationItem from './ScheduledReservationItem.vue';
import type { UseQueryType } from '@shared/hooks';
import type { ReservationType } from '@common/types';

defineEmits(['open:menu']);
type PropsType = {
    reservations: UseQueryType<Record<string, ReservationType[]>>;
};

defineProps<PropsType>();

const details = useReservationDetails();
</script>
<template>
    <div class="w-full md:px-3 text-slate-900 rounded-b-xl grid py-8">
        <div v-if="reservations.fetching" class="flex-center py-36">
            <Spinner class="w-8 h-8" />
        </div>
        <ul v-else-if="Object.keys(reservations.data).length" role="list" class="space-y-5 srb">
            <template v-for="(group, date) in reservations.data" :key="date">
                <li class="relative flex gap-x-3 -my-3">
                    <div :class="['-bottom-6', 'absolute left-0 top-0 flex w-6 justify-center']">
                        <div class="w-px bg-gray-300" />
                    </div>
                    <div class="relative flex size-6 flex-none items-center justify-center bg-gray-100">
                        <div class="size-1.5 rounded-full bg-gray-200 ring-1 ring-gray-400" />
                    </div>
                    <p class="flex-auto py-0.5 text-xs/5 text-gray-500">
                        Le <b class="text-dark">{{ dateFormat(date, 'letter') }}</b> Vous avez
                        <b class="text-dark">{{ group.length }}</b> formations ce jour:
                    </p>
                </li>

                <li v-for="item in group" :key="item.id" class="relative flex gap-x-2.5">
                    <div :class="['-bottom-8', 'absolute left-0 top-0 flex w-6 justify-center line']">
                        <div class="w-px bg-gray-300" />
                    </div>
                    <ScheduledReservationItem :item="item" @click="details.open(item)" />
                </li>
            </template>
        </ul>

        <EmptyState v-else heading="Aucune réservation" class="w-full py-20 max-md:mt-5" :image="CalendarIcon">
            <p>Vous n'avez aucune réservation</p>
        </EmptyState>
    </div>
</template>
<style scoped>
.srb li:last-child .line {
    @apply bottom-auto;
}
</style>
