<script setup lang="ts">
import moment from 'moment-timezone';
import { ref, watch } from 'vue';
import VueDatePicker, { RangeConfig } from '@vuepic/vue-datepicker';
import { useEvents, useRoute } from '@shared/hooks';
type TypeProps = {
    modelValue?: {
        start: string;
        end: string;
    };
    presetRanges?: Array<{ label: string; value: [string, string] }>;
    placeholder?: string;
    prefix?: string;
    range?: boolean | RangeConfig;
};
const props = defineProps<TypeProps>();

const emit = defineEmits(['update:model-value']);
const events = useEvents();
const params = useRoute<{ start: string; end: string }>();
const date = ref<string[]>([props.modelValue?.start ?? params.start ?? '', props.modelValue?.end ?? params.end ?? '']);

function getFormat(d = '') {
    return moment(String(d)).format('yyyy-MM-DD');
}

watch(date, (value) => {
    events.emit('table:filter:loading', { value: true });
    const start = value?.[0] ? getFormat(value[0]) : null;
    const end = value?.[1] ? getFormat(value[1]) : null;
    params.set(
        { start, end },
        {
            onFinish: () => {
                events.emit('table:filter:loading', { value: false });
            },
        }
    );
});
const format = ([date1, date2]: [string, string]) => {
    const start = moment(date1).format('DD/MM/YYYY');
    const end = moment(date2).format('DD/MM/YYYY');

    return `${props.prefix || ''}${start} au ${end}`;
};

const presetRanges = ref(
    props.presetRanges || [
        { label: "Aujourd'hui", value: [new Date(), new Date()] },
        {
            label: 'hier',
            value: [moment().subtract(1, 'd').format('YYYY-MM-DD'), moment().format('YYYY-MM-DD')],
        },
        {
            label: 'Les 7 derniers jours',
            value: [moment().subtract(7, 'd').format('YYYY-MM-DD'), moment().format('YYYY-MM-DD')],
        },
        {
            label: 'Ce mois-ci',
            value: [moment().startOf('month').format('YYYY-MM-DD'), moment().endOf('month').format('YYYY-MM-DD')],
        },
        {
            label: 'Le mois dernier',
            value: [
                moment().subtract(1, 'months').startOf('month').format('YYYY-MM-DD'),
                moment().subtract(1, 'months').endOf('month').format('YYYY-MM-DD'),
            ],
        },
    ]
);
</script>

<template>
    <VueDatePicker
        v-model="date"
        multi-calendars
        :enable-time-picker="false"
        :preset-dates="presetRanges"
        :range="range || {}"
        close-on-scroll
        dark
        color="orange"
        :placeholder="placeholder"
        select-text="Valider"
        cancel-text="Annuler"
        :ui="{ input: 'filter-control pr-7 min-w-60 w-full' }"
        class="text-xs"
        locale="fr-FR"
        :format="format"
    >
    </VueDatePicker>
</template>
