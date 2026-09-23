<script setup lang="ts">
import { reactive, computed, watch } from 'vue';
import moment from 'moment-timezone';
import { ButtonGroup, Dialog, Spinner } from '@shared/components';
import { ArrowLeftIcon, ArrowRightIcon } from '@adersolutions/icons';
import { currentDate, parsedDates } from '@shared/utils';
import { useEvents, useRoute } from '@shared/hooks';
import { onMounted } from 'vue';
import { nextTick } from 'vue';

type PropsType = {
    show?: boolean;
    loading?: boolean;
    // modelValue?: string;
    events: Record<string, { reserved: number; dispo: number }>;
    range?: boolean; // Enables week selection
};

const dayModel = defineModel({ type: String as () => string });
const weekModel = defineModel('week', { type: (Object as () => { start: string; end: string }) || null, default: null });

const emit = defineEmits(['close', 'refetch', 'change:week', 'change:date']);
const props = defineProps<PropsType>();
const params = useRoute<{ date: string }>(false);
// const event = useEvents();
const daysOfWeek = moment.weekdaysShort(true);
const event = useEvents();

// Get ISO week range
const getWeekRange = (dateStr: string) => {
    const dayMoment = moment(dateStr);
    return {
        start: dayMoment.startOf('isoWeek').format('YYYY-MM-DD'),
        end: dayMoment.endOf('isoWeek').format('YYYY-MM-DD'),
    };
};

const state = reactive({
    show: props.show,
    date: dayModel.value || params.date || currentDate,
    hoveredWeek: null as number | null,
    selectedWeek: weekModel.value || getWeekRange(dayModel.value || params.date || currentDate),
});

const currentDateMoment = computed(() => moment(state.date));
const month = computed(() => currentDateMoment.value.month());
const year = computed(() => currentDateMoment.value.year());
const totalDays = computed(() => currentDateMoment.value.daysInMonth());
const firstDayOfMonth = computed(() => moment([year.value, month.value, 1]).isoWeekday());

// Get previous month's trailing days
const prevMonthDays = computed(() => {
    const prevMonth = moment([year.value, month.value, 1]).subtract(1, 'month');
    const daysInPrevMonth = prevMonth.daysInMonth();
    return [...Array(firstDayOfMonth.value - 1)].map((_, i) => ({
        day: daysInPrevMonth - (firstDayOfMonth.value - 2) + i,
        date: prevMonth.date(daysInPrevMonth - (firstDayOfMonth.value - 2) + i).format('YYYY-MM-DD'),
        isCurrentMonth: false,
    }));
});

// Get next month's leading days
const nextMonthDays = computed(() => {
    const nextMonth = moment([year.value, month.value, 1]).add(1, 'month');
    const remainingDays = 7 - ((totalDays.value + prevMonthDays.value.length) % 7);
    return [...Array(remainingDays === 7 ? 0 : remainingDays)].map((_, i) => ({
        day: i + 1,
        date: nextMonth.date(i + 1).format('YYYY-MM-DD'),
        isCurrentMonth: false,
    }));
});

// Get all days (including extra days from adjacent months)
const allDays = computed(() => [
    ...prevMonthDays.value,
    ...[...Array(totalDays.value)].map((_, i) => ({
        day: i + 1,
        date: moment([year.value, month.value, i + 1]).format('YYYY-MM-DD'),
        isCurrentMonth: true,
    })),
    ...nextMonthDays.value,
]);

// Function to check if a day belongs to a hovered or selected week
const isWeekHighlighted = (dateStr: string) => {
    const { start, end } = getWeekRange(dateStr);
    return state.hoveredWeek === moment(start).isoWeek() || (state.selectedWeek && state.selectedWeek.start === start);
};

// Function to handle day click
const onDayClick = (dateStr: string) => {
    state.date = dateStr;
    if (props.range) state.selectedWeek = getWeekRange(dateStr);
};

// Handle month change
const onChangeMonth = (step: number) => {
    state.date = currentDateMoment.value.clone().add(step, 'month').format('YYYY-MM');
};
const onClose = () => {
    state.show = false;
    emit('close');
};
const onSelect = () => {
    if (props.range) {
        weekModel.value = state.selectedWeek;
    } else {
        dayModel.value = state.date;
        params.set({ date: state.date });
        event.emit(event.keys.schedule.date, state.date);

        // emit('change:date', { date: state.date, params: parsedDates(state.date) });
    }
    onClose();
};
watch(dayModel, (newDate, oldDate) => {
    const newWeek = moment(newDate).isoWeek();
    const oldWeek = moment(oldDate).isoWeek();
    if (newWeek !== oldWeek) {
        emit('refetch', {
            params: parsedDates(newDate),
            date: newDate,
        });
    }
    if (newDate) {
        state.date = newDate;
    }
});
watch(allDays, (days) => {
    days?.length && emit('change:week', { start: days[0].date, end: days[days.length - 1].date });
});
onMounted(() => {
    nextTick(() => {
        if (allDays.value?.length) {
            emit('change:week', { start: allDays.value[0].date, end: allDays.value[allDays.value.length - 1].date });
        }
    });
});
</script>

<template>
    <div class="contents">
        <div v-if="$slots.default" :class="[$attrs.class, '']" @click="state.show = true" class="contents">
            <slot> </slot>
        </div>
        <Dialog :show="show || state.show" class-name="!p-0" custom-class="modal-mobile !h-fit" max-width="xs" @close="onClose">
            <!-- Header -->
            <div class="bg-dark-block rounded-b-none">
                <div class="flex justify-between items-center p-2">
                    <button class="p-2 bg-gray-400/20 rounded-lg active:bg-primary" @click="onChangeMonth(-1)">
                        <ArrowLeftIcon class="w-5" />
                    </button>
                    <h2 class="text-lg font-semibold text-center">
                        {{ currentDateMoment.format('MMMM YYYY') }}
                        <p class="text-2xs text-gray-400 font-light">
                            <span v-if="state.selectedWeek && range">{{ state.selectedWeek.start }} à {{ state.selectedWeek.end }}</span>
                            <span v-else>{{ state.date }}</span>
                        </p>
                    </h2>
                    <button class="p-2 bg-gray-400/20 rounded-lg active:bg-primary" @click="onChangeMonth(1)">
                        <ArrowRightIcon class="w-5" />
                    </button>
                </div>
                <div class="h-rainbow"></div>

                <!-- Days of the week -->
                <div class="grid grid-cols-7 text-center text-gray-200 divide-x divide-white/10 text-sm">
                    <div v-for="day in daysOfWeek" :key="day" class="p-2">{{ day }}</div>
                </div>
            </div>
            <div class="rainbow -mt-px"></div>

            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 divide-x divide-y text-center bg-gray-200 relative">
                <div class="absolute inset-0 z-10 backdrop-blur-sm isolate flex-center" v-if="loading">
                    <Spinner class="w-6" />
                </div>
                <div
                    v-for="dayObj in allDays"
                    :key="dayObj.date"
                    @mouseover="state.hoveredWeek = moment(dayObj.date).isoWeek()"
                    @mouseleave="state.hoveredWeek = null"
                    @click="onDayClick(dayObj.date)"
                    :class="[
                        'relative p-1 btn-m t-3 aspect-square flex-center',
                        dayObj.isCurrentMonth ? 'bg-white' : 'bg-slate-100 text-gray-500',
                        range && isWeekHighlighted(dayObj.date) ? '!bg-indigo-200  border-indigo-400/10' : '',
                        dayObj.date === state.date && !range ? '!bg-indigo-200' : '',
                    ]"
                >
                    <span
                        :class="[
                            'text-2xs/3 z-1 absolute top-1 left-1',
                            dayObj.date === currentDate ? 'font-bold text-indigo-600' : 'opacity-30',
                        ]"
                        >{{ dayObj.day }}</span
                    >
                    <!-- Show event count if available -->
                    <p v-if="events[dayObj.date]" class="flex-center rounded-md text-xs overflow-clip font-bold">
                        <span
                            v-if="+events[dayObj.date].dispo"
                            :class="[
                                dayObj.date < currentDate ? 'text-dark/70 bg-dark/5' : 'text-green-100 bg-green-500',
                                'w-5 h-5 py-0.5',
                            ]"
                        >
                            {{ events[dayObj.date].dispo }}
                        </span>
                        <span
                            v-if="+events[dayObj.date].reserved"
                            :class="[dayObj.date < currentDate ? 'text-dark/70 bg-dark/5' : 'text-white bg-dark', 'w-5 h-5 py-0.5']"
                        >
                            {{ events[dayObj.date].reserved }}
                        </span>
                    </p>
                </div>
            </div>

            <div class="rainbow h-0.5 overflow-clip bg-gray-300"></div>
            <div class="p-2 bg-gray-200">
                <ButtonGroup
                    :actions="[
                        {
                            label: 'Fermer',
                            variant: 'secondary',
                            onAction: onClose,
                        },
                        {
                            label: 'Sélectionner',
                            variant: 'primary',
                            full: true,
                            onAction: onSelect,
                        },
                    ]"
                />
            </div>
        </Dialog>
    </div>
</template>
