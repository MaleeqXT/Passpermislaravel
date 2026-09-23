<script setup lang="ts">
import moment from 'moment-timezone';
import { useDebounce, useDimensions, useEvents, useRoute } from '@shared/hooks';
import { ArrowLeftIcon, ArrowRightIcon } from '@adersolutions/icons';
import { parsedDates, currentDate, getDate } from '@shared/utils';
import { getParams } from '@shared/utils';
import { onUnmounted } from 'vue';

const emit = defineEmits(['change:date', 'change']);
const props = defineProps({
    classNav: String,
    api: Boolean,
});
const dateOffset = defineModel({
    type: String,
    default: String(getParams().date || currentDate),
});
// const dateOffset = defineModel('date', {
//     type: String,
//     default: String(getParams().date || currentDate),
// });

// const weekOffset = defineModel('week', {
//     type: Number,
//     default: Number(getParams().w || 0),
// });
const daysOfWeek = moment.weekdaysShort(true); // ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const { md } = useDimensions();
const params = useRoute(!props.api);
const events = useEvents();
const debounce = useDebounce();

const onChange = (date: string): void => {
    dateOffset.value = date;
    params.set({ date });
    events.emit(events.keys.schedule.date, date);
    emit('change:date', {
        date,
        params: parsedDates(date),
    });
};

const onDateChange = debounce((day: number): void => {
    if (md.value) return;
    const date = getDate(dateOffset.value, day);
    onChange(date);
});
const onWeekChange = debounce((direction: number): void => {
    const date = moment(dateOffset.value).add(direction, 'week').format('YYYY-MM-DD');
    emit('change', {
        date,
        params: parsedDates(date),
    });
    onChange(date);
});

onUnmounted(() => {
    // events.off(events.keys.schedule.week);
    events.off(events.keys.schedule.date);
});
</script>
<template>
    <ul class="flex items-center px- w-full py-1.5 md:py-4 lg:shadow-down bg-dark/10 relative z-10 max-md:px-1">
        <li :class="['navigate-week', props.classNav]" @click="onWeekChange(-1)">
            <ArrowLeftIcon class="w-5" />
        </li>
        <li
            v-for="(name, day) in daysOfWeek"
            :key="day"
            :class="[
                'flex-center flex-col rounded-lg t-2 uppercase flex-1 h-full select-none leading-tight',
                currentDate === getDate(dateOffset, day) && 'text-primary',
                !md && 'md:hover:bg-opacity-20 cursor-pointer active:scale-95 active:opacity-70',
            ]"
            @click="onDateChange(day)"
        >
            <span
                :class="[
                    'text-sm font-semibold w-5 h-5 flex-center rounded-full',
                    dateOffset === getDate(dateOffset, day) && 'max-md:bg-primary max-md:text-white',
                ]"
            >
                {{ getDate(dateOffset, day, 'DD') }}
            </span>
            <span class="text-sm text-gray-500">
                <small class="md:hidden">{{ name.slice(0, 2) }}</small>
                <small class="max-md:hidden"> {{ name }}</small>
            </span>
        </li>
        <li :class="['navigate-week ', props.classNav]" @click="onWeekChange(1)">
            <ArrowRightIcon class="w-5" />
        </li>
    </ul>
</template>
<style lang="scss" scoped>
.navigate-week {
    @apply flex-center btn-m bg-gray-500/20 rounded-lg w-9 md:w-16 h-9;
}
</style>
