<script setup lang="ts">
import { Spinner } from '@shared/components';
import { useDimensions, useEvents, useRoute } from '@shared/hooks';
import { hours } from '@common/enums';
import { currentDate, getDate, getDay } from '@shared/utils';

import ScheduleEmptyItem from './ScheduleEmptyItem.vue';
const emit = defineEmits(['action:empty']);
defineProps({
    desktop: Boolean,
    attachable: {
        type: Boolean,
        default: true,
    },
    loading: Boolean,
});
const { md } = useDimensions();
const params = useRoute<{ date: string }>(false);
const events = useEvents();

events.on(events.keys.schedule.date, (value) => {
    params.date = value;
});
</script>
<template>
    <section>
        <article class="flex flex-auto">
            <div class="w-12 md:w-16 flex-none relative overflow-clip">
                <div
                    class="v-rainbow absolute right-0 inset-y-0 after:!h-full after:opacity-40 before:opacity-40 border-r saturate-50"
                ></div>
            </div>
            <div class="grid flex-auto grid-cols-1 grid-rows-1">
                <!-- Vertical lines -->
                <div
                    :class="[
                        'col-start-1 col-end-2 row-start-1 hidden grid-rows-1 divide-x divide-gray-300 ',
                        desktop && 'md:grid md:grid-cols-7',
                    ]"
                >
                    <div class="col-start-1 row-span-full" />
                    <div class="col-start-2 row-span-full" />
                    <div class="col-start-3 row-span-full" />
                    <div class="col-start-4 row-span-full" />
                    <div class="col-start-5 row-span-full" />
                    <div class="col-start-6 row-span-full" />
                    <div class="col-start-7 row-span-full" />
                    <div class="col-start-8 row-span-full w-16" />
                </div>

                <!-- Horizontal lines -->
                <div
                    class="col-start-1 col-end-2 row-start-1 grid divide-y divide-gray-300/60"
                    :style="{ gridTemplateRows: `repeat(${hours.length}, minmax(3.5rem, 1fr))` }"
                >
                    <div class="row-end-1 h-7" />
                    <div v-for="(time, idx) in hours" :key="idx">
                        <div class="-ml-16 -mt-2.5 w-16 pr-2 text-right text-xs leading-5 text-gray-400">
                            {{ time > 9 ? time : '0' + time }}:00
                        </div>
                    </div>
                </div>
                <ul
                    :class="[
                        'col-start-1 col-end-2 row-start-1 grid grid-cols-1  ',
                        desktop && 'md:grid-cols-7 md:pr-16',
                        loading && 'blur-sm',
                    ]"
                    :style="{ gridTemplateRows: `1.75rem repeat(${hours.length}, minmax(0, 1fr)) auto` }"
                >
                    <template v-if="md && desktop">
                        <template v-for="(_, count) in 7" :key="count">
                            <ScheduleEmptyItem
                                v-if="attachable"
                                :date="getDate(params.date || currentDate, count)"
                                :count="count"
                                @select="$emit('action:empty', $event)"
                            />
                            <slot :count="count" :date="getDate(params.date || currentDate, count)" :is-desktop="md && desktop" />
                        </template>
                    </template>
                    <template v-else>
                        <ScheduleEmptyItem v-if="attachable" :date="params.date || currentDate" @select="$emit('action:empty', $event)" />
                        <slot :count="0" :date="params.date || currentDate" :is-desktop="md && desktop" />
                    </template>
                </ul>
            </div>
        </article>

        <article v-if="loading" class="absolute inset-0 bg-white/70 top-0 left-0 z-10 flex-center rounded-xl">
            <Spinner class="w-8" />
        </article>
    </section>
</template>
