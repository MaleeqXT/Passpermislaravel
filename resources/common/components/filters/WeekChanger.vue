<script setup lang="ts">
import moment from 'moment-timezone';
import { ArrowLeftIcon, ArrowRightIcon } from '@adersolutions/icons';
import { onMounted } from 'vue';
import { useDebounce } from '@shared/hooks';

const emit = defineEmits(['change', 'open:schedule']);
const props = defineProps<{ mounted?: boolean }>();

// Use defineModel() for two-way binding with start and end ISO dates
const dateRange = defineModel<{ start: string; end: string }>({
    default: () => ({
        start: moment().startOf('isoWeek').format('yyyy-MM-DD'),
        end: moment().endOf('isoWeek').format('yyyy-MM-DD'),
    }),
});

const debounce = useDebounce();

// Calculate the week range based on the provided start date
const getWeekRange = (startDate: string) => {
    const startOfWeek = moment(startDate).startOf('isoWeek');
    const endOfWeek = startOfWeek.clone().endOf('isoWeek');
    return {
        start: startOfWeek.format('ddd DD MMMM'),
        end: endOfWeek.format('ddd DD MMMM'),
        params: {
            date_1: startOfWeek.format('yyyy-MM-DD'),
            date_2: endOfWeek.format('yyyy-MM-DD'),
        },
    };
};

// Change the week by adding/subtracting weeks from the current start date
const changeWeek = debounce((direction: number) => {
    const newStartDate = moment(dateRange.value.start).add(direction, 'week').format('yyyy-MM-DD');
    const newEndDate = moment(newStartDate).endOf('isoWeek').format('yyyy-MM-DD');

    dateRange.value = { start: newStartDate, end: newEndDate };

    emit('change', {
        start: newStartDate,
        end: newEndDate,
    });
});

// Initialize the week range on mount if needed
onMounted(() => {
    if (props.mounted) {
        const { start, end } = dateRange.value;
        emit('change', {
            start,
            end,
        });
    }
});
</script>

<template>
    <div class="flex items-center gap-2 px-2 py-1 bg-gray-100/60 backdrop-blur-md md:rounded-lg">
        <button class="btn-nav" @click.prevent="changeWeek(-1)">
            <ArrowLeftIcon class="w-5 h-5" />
        </button>
        <span
            class="text-sm font-medium text-dark flex-1 flex-center bg-dark/10 shadow-inner h-9 rounded-lg border-b-[0.5px] border-l-[0.5px] border-dark/5"
            @click="$emit('open:schedule')"
        >
            {{ getWeekRange(dateRange.start).start }}
            <ArrowRightIcon class="w-4 block-inline mx-3 opacity-40" />
            {{ getWeekRange(dateRange.start).end }}
        </span>
        <button class="btn-nav" @click.prevent="changeWeek(1)">
            <ArrowRightIcon class="w-5 h-5" />
        </button>
    </div>
</template>

<style scoped>
.btn-nav {
    @apply flex items-center justify-center w-9 h-9 rounded-lg bg-dark/10 md:hover:bg-gray-200 active:bg-gray-300 border-b-[0.5px] border-l-[0.5px] border-dark/5 shadow-inner;
}
</style>
