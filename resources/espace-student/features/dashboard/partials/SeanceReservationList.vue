<script setup>
import { computed } from 'vue';
import { BookOpenIcon } from '@adersolutions/icons';
import { EmptyState } from '@shared/components';
import SeanceItem from './SeanceItem.vue';
import moment from 'moment-timezone';
defineEmits(['select']);
const props = defineProps({
    reservations: Object,
    isPassed: Boolean,
});
const list = computed(() => {
    return props.isPassed ? props.reservations.passed.data : props.reservations.next.data;
});
const getMonth = (date, idx) => {
    const month = moment(date).format('MMMM');
    const count = list.value.filter((s) => moment(s.date).format('MMMM') === month);
    const obj = {
        month: month,
        count: count.length,
    };
    if (idx === 0) return obj;

    if (month !== moment(list.value[idx - 1].date).format('MMMM')) return obj;

    return '';
};
</script>

<template>
    <ul role="list" class="space-y-6">
        <template v-for="(item, idx) in list" :key="item.id">
            <li v-if="getMonth(item.date, idx)" class="relative flex gap-x-4">
                <div :class="[idx === list.length - 1 ? 'h-6' : '-bottom-6', 'absolute left-0 top-0 flex w-6 justify-center']">
                    <div class="w-px bg-gray-200" />
                </div>
                <div class="relative flex size-6 flex-none items-center justify-center bg-gray-100">
                    <div class="size-1.5 rounded-full bg-gray-100 ring-1 ring-gray-300" />
                </div>
                <p class="flex-auto py-0.5 text-xs/5 text-gray-500">
                    il y a <b class="text-dark">{{ getMonth(item.date, idx).count }}</b> formations ce mois:
                    <b class="text-dark">{{ getMonth(item.date, idx).month }}</b>
                </p>
            </li>

            <li class="relative flex gap-x-3">
                <div :class="[idx === list.length - 1 ? 'h-6' : '-bottom-6', 'absolute left-0 top-0 flex w-6 justify-center']">
                    <div class="w-px bg-gray-200" />
                </div>
                <SeanceItem :item="item" :is-passed="isPassed" @click="$emit('select', item)" />
            </li>
        </template>

        <EmptyState
            v-if="!list.length"
            heading="Aucune formation"
            class="w-full py-20 max-md:mt-5"
            :loading="reservations.passed.fetching || reservations.next.fetching"
            :image="BookOpenIcon"
        >
            <p>Vous n'avez aucune formation</p>
        </EmptyState>
    </ul>
</template>
