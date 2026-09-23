<script setup lang="ts">
import { computed } from 'vue';
import { getReservationDate, useReservations } from '../../ReservationsPage';
import { MonthlyCountSchedule } from '@common/components';
import { STORAHE_KEY_RESERVATION } from '../../ReservationsPage';

const props = defineProps<{ uniqueMonitors: { count: number; list: any[] } }>();
const { filters } = useReservations();

const days = computed(() => {
    const result: { d: string; date: string }[] = [];
    for (let i = 0; i < 7; i++) {
        result.push({ d: getReservationDate(i, 'DD'), date: getReservationDate(i) });
    }
    return result;
});
</script>

<template>
    <div class="grid-rows-2 grid gap-3 grid-flow-col auto-cols-fr lg:flex-none lg:grid">
        <div class="flex items-center gap-2">
            <MonthlyCountSchedule :start="getReservationDate(0)" :data="{}" :monitors="props.uniqueMonitors.list" />
        </div>
        <div class="grid grid-cols-7 gap-1 w-full">
            <div v-for="(day, i) in days" :key="i" class="text-xs text-gray-400 text-left">{{ day.d }} </div>
        </div>
    </div>
</template>
