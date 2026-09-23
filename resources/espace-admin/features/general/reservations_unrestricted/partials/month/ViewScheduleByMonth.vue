<script setup lang="ts">
import moment from 'moment-timezone';
import { ref, computed } from 'vue';
import LessonMonthItem from './LessonMonthItem.vue';
import { listDays, useReservations } from '../../ReservationsPage';
import ShowMoreLessonDialog from '../dialogs/ShowMoreLessonDialog.vue';
import { currentDate } from '@shared/utils';
import { PlusIcon } from '@adersolutions/icons';
import type { ReservationType } from '@common/types';

const { filters, query, state, activeMonth } = useReservations();

const moreReservations = ref<ReservationType[] | null>(null);

const getSlicedList = (day: string) => {
    const length = query.data?.[day]?.length;
    if (length > 3) {
        return query.data?.[day]?.slice(0, 3);
    }
    return query.data?.[day];
};

const daysInMonth = computed(() => {
    if (!filters.start || !filters.end) return [];

    const days = [];
    let currentDate = moment(filters.start).startOf('isoWeek');
    const lastDate = moment(filters.end).endOf('isoWeek');
    while (currentDate.isSameOrBefore(lastDate, 'day')) {
        days.push({
            d: currentDate.date(),
            date: currentDate.format('YYYY-MM-DD'),
            inCurrentMonth: currentDate.month() === activeMonth().month(),
        });
        currentDate.add(1, 'day'); // Move to next day
    }

    return days;
});
const onEventInSchedule = (date: string) => {
    state.selected = {
        date,
        start_at: '08:00',
        end_at: '09:00',
    };
    state.show = true;
};
</script>

<template>
    <div class="shadow ring-1 ring-dark ring-opacity-5 lg:flex lg:flex-auto lg:flex-col">
        <div
            class="grid grid-cols-7 text-center text-xs font-semibold leading-6 text-white bg-dark lg:flex-none relative divide-x divide-gray-400/50"
        >
            <div v-for="day in listDays" :key="day" class="py-2 relative z-1">
                {{ day }}
            </div>
        </div>
        <div class="flex bg-gray-200 text-xs leading-6 text-gray-500 lg:flex-auto">
            <div class="w-full grid grid-cols-7 grid-rows-5 gap-px">
                <div
                    v-for="day in daysInMonth"
                    :key="day.date"
                    :class="[day.inCurrentMonth ? 'bg-white' : 'bg-gray-100 text-gray-500', 'relative px-2 py-1 min-h-32']"
                >
                    <div class="flex justify-between">
                        <time
                            :class="[
                                currentDate === day.date &&
                                    'flex h-6 w-6 items-center justify-center rounded-full bg-primary font-semibold text-white',
                                'relative z-1',
                            ]"
                            :datetime="day.date"
                        >
                            {{ day.d }}
                        </time>
                        <span
                            v-if="moment(day.date).diff(moment(), 'd') >= 0 && query.data?.[day.date]?.length > 0"
                            class="rounded-full hover:bg-primary text-gray-500 hover:text-white relative z-10 cursor-pointer btn-m flex-center size-6 -mr-1"
                        >
                            <PlusIcon class="size-4" />
                        </span>
                    </div>

                    <ol v-if="query.data?.[day.date]?.length > 0" class="mt-1 flex flex-col gap-0.5">
                        <LessonMonthItem
                            v-for="item in getSlicedList(day.date)"
                            :key="item.id"
                            :item="item"
                            @select="state.selected = item"
                        />
                        <li v-if="query.data?.[day.date]?.length > 3" class="text-gray-500">
                            <button class="hover:text-primary hover:underline" @click="moreReservations = query.data?.[day.date]">
                                + {{ query.data?.[day.date]?.length - 3 }} autres
                            </button>
                        </li>
                    </ol>
                    <button
                        v-else-if="moment(day.date).diff(moment(), 'd') >= 0"
                        class="absolute inset-1 z-0 hover:bg-dark/5 transition-all duration-300 rounded-lg flex-center group btn-m"
                        @click="onEventInSchedule(day.date)"
                    >
                        <span class="text-gray-500 opacity-0 group-hover:opacity-100 t-3">Ajouter</span>
                    </button>
                </div>
            </div>
        </div>
        <ShowMoreLessonDialog :list="moreReservations" @close="moreReservations = null" />
    </div>
</template>
