<script setup lang="ts">
import moment from 'moment-timezone';
import { hours } from '@common/enums';
import { getDate, getHour, isOutdated } from '@shared/utils';
import { computed } from 'vue';

const emit = defineEmits(['select']);
type PorpsType = { count?: number; date: string };
const props = withDefaults(defineProps<PorpsType>(), {
    count: 0,
});

// date_hour,
// end_at: moment(start_at, 'HH:mm').add(1, 'hour').format('HH:mm'),
// disabled_for: (elPrev?.end_at ?? '') > start_at ? elPrev : null,
// start_at,
// date: state.day,
// is_active: true,
// hour: 1,
// lieu_id: undefined,
// all: true,
// const parsedDate = computed(() => (props.isDateAbsolute ? props.date : getDate(props.date, props.count as number)));
const onSelectedDate = (time: number) => {
    emit('select', {
        date_hour: time,
        start_at: getHour(time),
        end_at: moment(getHour(time), 'HH:mm').add(1, 'hour').format('HH:mm'),
        date: props.date,
        hour: 1,
        is_active: true,
    });
};
</script>
<template>
    <div v-for="(time, idx) in hours" :key="idx" class="contents">
        <li
            v-if="!isOutdated({ date, end_at: getHour(time) })"
            :class="['relative z-0 col-start-' + (count + 1)]"
            :style="{ gridRow: `${time - 5}/ span 1` }"
        >
            <div
                class="absolute inset-1 flex-center text-sm text-gray-600 hover:bg-gray-100 rounded-lg btn-m group/time"
                @click="onSelectedDate(time)"
            >
                <span class="group-hover/time:hidden block opacity-20">+</span>
                <span class="group-hover/time:block hidden text-xs">{{ getHour(time) }} - {{ getHour(time + 1) }}</span>
            </div>
        </li>
    </div>
</template>
