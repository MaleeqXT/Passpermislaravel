<script setup lang="ts">
import moment from 'moment-timezone';
import { reactive, watch, nextTick } from 'vue';
import { Button, Popup, Errors } from '@shared/components';
import { CalendarIcon, XIcon } from '@adersolutions/icons';
import InputField from './InputField.vue';

interface DatePickerProps {
    modelValue: string | null | undefined;
    error?: string | string[];
    label?: string;
    customClass?: string;
    placeholder?: string;
    birthday?: boolean;
    clear?: boolean;
    filter?: boolean;
    monthPicker?: boolean;
    length?: number;
    minDate?: string | Date | undefined | null;
    maxDate?: string | Date | undefined | null;
    minTime?: string | null;
    maxTime?: string | null;
    time?: boolean;
    disabled?: boolean;
}

interface DatePickerState {
    year: number;
    month: number;
    day: number;
    hour: number | null;
    minute: number | null;
    value: string | null;
}

interface TimeData {
    hours: number[];
    minutes: number[];
}

interface DatePickerData {
    days: number;
    years: number[];
    months: { name: string; value: number }[];
}

const emit = defineEmits(['update:model-value', 'change']);
const props = defineProps<DatePickerProps>();

const id = 'datepicker-' + Math.random().toString(36).substring(2, 11);
const formatType = props.monthPicker ? 'yyyy-MM' : 'yyyy-MM-DD';

const getInitialData = (): DatePickerState => {
    if (props.time) {
        return {
            hour: moment(props.modelValue || undefined, 'HH:mm').hour(),
            minute: moment(props.modelValue || undefined, 'HH:mm').minute(),
            value: props.modelValue || null,
        } as DatePickerState;
    }
    const y = moment(props.modelValue || undefined)
        .subtract(!props.modelValue && props.birthday ? 16 : 0, 'years')
        .year();
    return {
        year: y,
        month: moment(props.modelValue || new Date()).month() + 1,
        day: props.monthPicker ? 1 : moment(props.modelValue || new Date()).date(),
        value: props.modelValue ? moment(props.modelValue, formatType).format(formatType) : null,
    } as DatePickerState;
};

const state = reactive<DatePickerState>(getInitialData());

const getDefaultTime = ({ hour, minute }: { hour?: number; minute?: number } = {}): TimeData => {
    const minTime = moment(props.minTime || '07:00', 'HH:mm');
    const maxTime = moment(props.maxTime || '23:00', 'HH:mm');
    console.info(hour, minute);
    const availableHours: number[] = [];
    let availableMinutes = [0, 15, 30, 45];
    const start = minTime.hours() >= 23 ? 7 : minTime.hours();

    for (let h = start; h <= maxTime.hours(); h++) {
        availableHours.push(h);
    }
    if (state.hour === start) {
        availableMinutes = availableMinutes.filter((m) => m >= minTime.minutes());
    }
    return {
        hours: availableHours,
        minutes: availableMinutes,
    };
};

const getDefaultdata = ({ year, month }: { year: number; month: number }): DatePickerData => {
    const min = moment()
        .subtract(props.birthday ? 100 : 20, 'years')
        .toDate();
    const max = props.birthday ? moment().subtract(16, 'years').toDate() : props.maxDate;

    const startYear = moment(min).year();
    const endYear = max ? moment(max).year() : moment().year() + 10;

    const months = moment.months().map((name, value) => ({ name, value: value + 1 }));

    const days = moment(`${year}-${month}`, 'YYYY-MM').daysInMonth();
    return {
        days: days,
        years: Array.from({ length: endYear - startYear + 1 }, (_, i) => startYear + i),
        months: months,
    };
};

const data = reactive<DatePickerData>(getDefaultdata({ year: state.year, month: state.month }));
const timeData = reactive<TimeData>(getDefaultTime());

const handleClear = () => {
    state.value = null;
    emit('update:model-value', null);
    emit('change', null);
};

const onApply = (close: () => void) => {
    close?.();
    if (props.time) state.value = moment(`${state.hour}:${state.minute}`, 'HH:mm').format('HH:mm');
    else state.value = moment(`${state.day}-${state.month}-${state.year}`, 'DD-MM-YYYY').format(formatType);
    emit('update:model-value', state.value);
    emit('change', state.value);
};

watch(state, ({ year, month, hour, minute }) => {
    if (props.time) {
        const values = getDefaultTime({ hour: hour ?? undefined, minute: minute ?? undefined });
        timeData.hours = values.hours;
        timeData.minutes = values.minutes;
    } else {
        const values = getDefaultdata({ year, month });
        data.months = values.months;
        data.days = values.days;
    }
});

const handleOpen = async ({ year, month, day, hour, minute } = state) => {
    await nextTick();
    if (props.time) {
        scrollToActiveButton(`#hour-${id}`, hour);
        scrollToActiveButton(`#minute-${id}`, minute);
    } else {
        scrollToActiveButton(`#year-${id}`, year);
        scrollToActiveButton(`#month-${id}`, month);
        if (!props.monthPicker) {
            scrollToActiveButton(`#day-${id}`, day);
        }
    }
};

const scrollToActiveButton = (selector: string, value: number | null) => {
    const element = window.document?.querySelector(`${selector} [data-idx="${value}"]`);
    if (element) {
        element.scrollIntoView({ block: 'center', behavior: 'smooth', inline: 'nearest' });
        if (window.document.scrollingElement) {
            window.document.scrollingElement.scrollTop = window.document.scrollingElement.scrollTop || 0;
        }
    }
};

watch(state, handleOpen);
watch(props, () => {
    if (props.time) {
        const values = getDefaultTime();
        timeData.hours = values.hours;
        timeData.minutes = values.minutes;
    } else {
        const values = getDefaultdata({ year: state.year, month: state.month });
        data.months = values.months;
        data.days = values.days;
    }
});
</script>
<template>
    <div class="w-full">
        <Popup
            :arrow="false"
            popper-classes="p-0 !min-w-80 md:max-w-lg"
            mobile
            class="w-full"
            z-index="z-[999]"
            :disabled="disabled"
            @popover-opened="handleOpen(state)"
        >
            <InputField v-if="time" :label="label" :model-value="state.value" readonly :disabled="disabled" type="time" />
            <div
                v-else-if="monthPicker"
                :class="['flex items-center  gap-px ', customClass || (filter ? 'filter-control !text-xs' : 'form-control min-h-9')]"
            >
                <InputField
                    :model-value="state.value ? moment(state.value, 'yyyy-MM').format('MMM yyyy') : ''"
                    :label="label"
                    :placeholder="placeholder || 'yyyy-MM'"
                    readonly
                    input-class="border-none p-0 appearence-none w-fit w-full bg-transparent focus:outline-none focus:ring-0 !select-none pl-2"
                    :disabled="disabled"
                />
                <div class="flex items-center gap-1 px-2 relative">
                    <XIcon v-if="state.value && clear" class="w-6 h-6 btn p-1 hover:bg-gray-200" @click.stop="handleClear" />
                    <CalendarIcon class="w-5" />
                </div>
            </div>
            <InputField
                v-else
                type="date"
                :label="label"
                :disabled="disabled"
                readonly
                :model-value="state.value"
                :class="[disabled && '!bg-gray-100 !text-gray-600 ']"
            />
            <template #content="{ close }">
                <div class="flex justify-between border-b bg-gray-200 rounded-t-xl p-1.5 mb-0 w-full min-w-60">
                    <Button variant="secondary" class="!p-2" @click="close()">Fermer</Button>
                    <Button
                        variant="dark"
                        class="!p-2"
                        :disabled="time ? !state.hour && !state.minute : !state.day || !state.month || !state.year"
                        @click="onApply(close)"
                    >
                        Valider
                    </Button>
                </div>
                <ul v-if="time" class="flex text-sm divide-x bg-white">
                    <li :id="'hour-' + id" class="flex-1 max-h-60 overflow-y-auto no-scrollbar flex-col flex p-1.5">
                        <button
                            v-for="hour in timeData.hours"
                            :key="hour"
                            :data-idx="hour"
                            :class="['btn-time btn', state.hour === hour && '!btn-header text-white']"
                            @click="state.hour = hour"
                        >
                            {{ hour }}
                        </button>
                        <span v-for="i in 5" :key="'start' + i" class="block p-3"></span>
                    </li>
                    <li :id="'minute-' + id" class="flex-1 max-h-60 overflow-y-auto no-scrollbar flex-col flex p-1.5">
                        <button
                            v-for="minute in timeData.minutes"
                            :key="minute"
                            :data-idx="minute"
                            :class="['btn-time btn', state.minute === minute && '!btn-header text-white']"
                            @click="state.minute = minute"
                        >
                            {{ minute?.toString().padStart(2, '0') }}
                        </button>
                        <span v-for="i in 5" :key="'start' + i" class="block p-3"></span>
                    </li>
                </ul>
                <div v-else class="flex text-xl divide-x bg-white">
                    <li :id="'year-' + id" class="flex-1 min-w-[30%] max-h-60 overflow-y-auto no-scrollbar flex-col flex p-1.5">
                        <span v-for="i in 5" :key="'start' + i" class="block p-3"></span>
                        <button
                            v-for="year in data.years"
                            :key="year"
                            :data-idx="year"
                            :class="[
                                'btn-time btn',
                                state.year === year && '!btn-header text-white',
                                minDate && moment(minDate, 'yyyy-MM-DD').year() > year && 'disable',
                            ]"
                            @click="state.year = year"
                        >
                            {{ year }}
                        </button>
                        <span v-for="i in 5" :key="'end' + i" class="block p-3"></span>
                    </li>
                    <li :id="'month-' + id" class="flex-auto max-h-60 overflow-y-auto no-scrollbar flex-col flex p-1">
                        <span v-for="i in 5" :key="'start' + i" class="block p-3"></span>
                        <button
                            v-for="month in data.months"
                            :key="month.value"
                            :data-idx="month.value"
                            :class="[
                                'btn-time btn',
                                state.month === month.value && '!btn-header text-white',
                                minDate &&
                                    moment(minDate, 'yyyy-MM-DD').month() >= month.value &&
                                    state.year === moment(minDate, 'yyyy-MM-DD').year() &&
                                    'disable',
                                maxDate &&
                                    moment(maxDate, 'yyyy-MM-DD').month() < month.value &&
                                    state.year === moment(maxDate, 'yyyy-MM-DD').year() &&
                                    'disable',
                            ]"
                            @click="state.month = month.value"
                        >
                            {{ month.name }}
                        </button>
                        <span v-for="i in 5" :key="'end' + i" class="block p-3"></span>
                    </li>
                    <li
                        v-if="!monthPicker"
                        :id="'day-' + id"
                        class="flex-1 min-w-[30%] max-h-60 overflow-y-auto no-scrollbar flex-col flex p-1"
                    >
                        <span v-for="i in 5" :key="'start' + i" class="block p-3"></span>
                        <button
                            v-for="day in data.days"
                            :key="day"
                            :data-idx="day"
                            :class="[
                                'btn-time btn',
                                state.day === day && '!btn-header text-white',
                                minDate &&
                                    moment(minDate, 'yyyy-MM-DD').format('yyyy-MM-DD') >
                                        `${state.year}-${state.month}-${day.toString().padStart(2, '0')}` &&
                                    'disable',
                            ]"
                            @click="state.day = day"
                        >
                            {{ day }}
                        </button>
                        <span v-for="i in 5" :key="'end' + i" class="block p-3"></span>
                    </li>
                </div>
            </template>
        </Popup>
        <Errors v-if="error" :errors="error" />
    </div>
</template>
<style scoped lang="scss">
.disable {
    @apply opacity-50 pointer-events-none;
}
.btn-time {
    @apply p-2 rounded-lg  w-full justify-center hover:bg-gray-100;
}
</style>
