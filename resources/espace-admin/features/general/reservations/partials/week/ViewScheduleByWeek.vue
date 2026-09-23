<script setup lang="ts">
import { computed } from 'vue';
import { extractUniqueMonitors, getReservationDate, useReservations } from '../../ReservationsPage';
import { ScheduleSection } from '@common/components';
import ScheduleLessonCard from './ScheduleLessonCard.vue';
import ViewScheduleHeaderWeek from './ViewScheduleHeaderWeek.vue';
import type { ReservationType } from '@common/types';
import { onMounted } from 'vue';
import { useEvents } from '@shared/hooks';
import { nextTick } from 'vue';

type PropsType = {
    data: Record<string, ReservationType[]>;
};
const { filters, state } = useReservations();
const events = useEvents();

const props = defineProps<PropsType>();
const uniqueMonitors = computed(() => extractUniqueMonitors(props.data));
const onAddExactTime = (v) => {
    console.log(v);
    const res = {
        ...v,
        is_active: 1,
    };
    if (filters.monitor_id?.[0]) {
        res.monitor = { user: filters.monitor_id[0] || null, id: filters.monitor_id[0].monitor?.id };
    }
    if (filters.student_id?.[0]) {
        res.training = { student: { user: filters.student_id[0] || null, id: filters.student_id[0]?.student?.id } };
    }
    if (filters.lieu_id) {
        res.lieu = { ...(filters.lieu_id || {}), zone: filters.zone_id };
    }
    state.selected = res;
    state.show = true;
};
onMounted(() => {
    nextTick(() => {
        setTimeout(() => {
            events.emit(events.keys.schedule.date, filters.start);
        }, 100);
    });
});
</script>

<template>
    <div class="isolate flex flex-auto flex-col overflow-auto">
        <div class="flex max-w-full flex-none flex-col sm:max-w-none md:max-w-full" :style="{ width: '165%' }">
            <ViewScheduleHeaderWeek :unique-monitors="uniqueMonitors" />
            <ScheduleSection v-slot="{ count, date }" desktop @action:empty="onAddExactTime">
                <ScheduleLessonCard
                    v-for="(reservation, key) in data[date]"
                    :key="key"
                    :class="['col-start-' + (count + 1)]"
                    :unique-monitors="uniqueMonitors[date]"
                    :item="reservation"
                    :count="count"
                    :index="key"
                />
            </ScheduleSection>
        </div>
    </div>
</template>
