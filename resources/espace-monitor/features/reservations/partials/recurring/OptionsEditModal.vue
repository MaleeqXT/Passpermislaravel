<script setup>
import moment from 'moment-timezone';
import { reactive, computed, watch } from 'vue';
import { Card, Button, CheckField, Dialog } from '@shared/components';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue';
import { getHoursByRange } from '../../ReservationsPage';
import OptionsHoursEditModal from './OptionsHoursEditModal.vue';
import { CheckIcon, ChevronUpIcon, EditIcon } from '@adersolutions/icons';

const emit = defineEmits(['close', 'update']);
const props = defineProps({
    item: Object,
});

const listOfHours = computed(() => {
    return (day) =>
        getHoursByRange(props.item.hour_start, props.item.hour_end).map((v) => {
            const el = state.days[day]?.find((el) => parseInt(el.start_at) === parseInt(v.start_at));
            return el ? el : v;
        });
});
const getSelectedDays = computed(() => {
    return props.item.selectedDays?.[props.item.optionEdit?.month?.format('yyyy-MM')] || {};
});

const state = reactive({
    show: false,
    showEdit: false,
    selectedHour: {
        start_at: '',
        end_at: '',
        date_hour: 7,
    },
    days: getSelectedDays.value || {},
});

const getCheckedOptions = computed(() => {
    return {
        hours: (day, hour) => {
            const dayHours = state.days[day] || [];
            return {
                checked: dayHours?.some((v) => parseInt(v.start_at) === parseInt(hour.start_at)) || false,
            };
        },
    };
});

const onUpdateHourModel = (day, { start_at, end_at }, checked) => {
    const month = props.item.optionEdit?.month.format('yyyy-MM');
    if (checked) {
        if (!state.days[day]) {
            state.days[day] = [];
        }
        state.days[day].push({ start_at, end_at });
    } else {
        state.days[day] = state.days[day].filter((v) => parseInt(v.start_at) !== parseInt(start_at));
        if (!state.days[day].length) {
            delete state.days[day];
        }
    }
    emit('update', { days: state.days, month });
};

const handleHourEdit = ({ start_at, end_at }, day) => {
    state.showEdit = true;
    state.selectedHour = JSON.parse(JSON.stringify({ start_at, end_at, day }));
};

const handleHourUpdate = ({ day, ...hour }) => {
    const month = props.item.optionEdit?.month.format('yyyy-MM');
    state.showEdit = false;
    state.selectedHour = { day, ...hour };
    let exist = 0;
    state.days[day] = state.days[day]?.map((v) => {
        if (parseInt(v.start_at) === parseInt(hour.start_at)) {
            exist++;
            return hour;
        }
        return v;
    });
    if (!exist) {
        onUpdateHourModel(day, hour, true);
    } else {
        emit('update', { days: state.days, month });
    }
};

const getday = (day) => {
    const m = props.item.optionEdit?.month.format('YYYY-MM-DD');
    return moment(m)?.set('D', day)?.format('dddd DD');
};

const isDisabled = ({ start_at }, day) => {
    const previousHour = moment(parseInt(start_at) - 1, 'HH:mm').format('HH:mm');
    const previousItem = state.days?.[day]?.find((v) => parseInt(v.start_at) === parseInt(previousHour));
    if (previousItem) {
        return previousItem.end_at > start_at;
    }
    return false;
};

watch(
    () => props.item.optionEdit,
    (optionEdit) => {
        if (optionEdit) {
            state.days = getSelectedDays.value || {};
        }
    }
);
</script>

<template>
    <div class="contents">
        <Dialog :show="!!item.optionEdit" mobile title="Planification Options des heures" z-index="z-[1000]" @close="$emit('close')">
            <div class="flex-1">
                <Card class="p-3 flex flex-col gap-2" :title="item.optionEdit?.month?.format('YYYY MMMM')" :separated="false" as="ul">
                    <Disclosure
                        v-for="(day, dayIdx) in item.optionEdit?.days || []"
                        :key="dayIdx"
                        v-slot="{ open }"
                        as="li"
                        :default-open="dayIdx === 0"
                        class="rounded-xl bg-white shadow-box-2"
                    >
                        <DisclosureButton
                            :key="dayIdx"
                            class="flex items-center w-full justify-between rounded-xl px-3 py-2.5 text-left text-md font-medium text-primary focus:outline-none capitalize"
                        >
                            <span>{{ getday(day) }}</span>
                            <ChevronUpIcon :class="!open ? 'rotate-180 transform' : ''" class="h-6 w-6 text-gray-400" />
                        </DisclosureButton>
                        <transition name="collapse">
                            <DisclosurePanel class="px-3 text-sm text-gray-500 overflow-clip border-t">
                                <ul class="divide-y">
                                    <li v-for="(hour, hourIdx) in listOfHours(day)" :key="hourIdx" class="py-2 flex justify-between">
                                        <CheckField
                                            :id="`${day}-hours-${hourIdx}`"
                                            :label="`${hour.start_at} - ${hour.end_at}`"
                                            :disabled="isDisabled(hour, day)"
                                            :checked="getCheckedOptions.hours(day, hour).checked"
                                            @update="onUpdateHourModel(day, hour, $event)"
                                        />
                                        <EditIcon class="w-7 btn-m p-1 -mr-1" @click="handleHourEdit(hour, day)" />
                                    </li>
                                </ul>
                            </DisclosurePanel>
                        </transition>
                    </Disclosure>
                </Card>
            </div>
            <div class="sticky md:bottom-0 bg-white max-md:left-0 bottom-0 shadow-box-2 h-fit z-1 md:max-w-xl w-full md:mx-auto p-2">
                <Button full :icon="CheckIcon" variant="dark" @click="emit('close')"> Fermer </Button>
            </div>
        </Dialog>
        <OptionsHoursEditModal
            :show="state.showEdit"
            :selected-hour="state.selectedHour"
            @close="state.showEdit = false"
            @update="handleHourUpdate"
        />
    </div>
</template>
