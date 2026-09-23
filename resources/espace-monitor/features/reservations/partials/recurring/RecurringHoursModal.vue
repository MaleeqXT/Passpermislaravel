<script setup lang="ts">
import { Card, CheckField, Drawer, Button } from '@shared/components';
import { computed, onMounted, reactive, watch } from 'vue';
import moment from 'moment-timezone';
import { useForm } from '@inertiajs/vue3';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue';
import { routes } from '@espace-monitor/routes';
import { useAlert } from '@shared/stores';
import { getDaysOfWeekInMonth, getHoursByRange, type SelectedDaysType } from '../../ReservationsPage';
import OptionsEditModal from './OptionsEditModal.vue';
import HoursPeriodFilterDialog from './HoursPeriodFilterDialog.vue';
import { hours } from '@common/enums';
import { ArrowRightIcon, CheckIcon, ChevronUpIcon, EditIcon, SettingsIcon } from '@adersolutions/icons';
import { dateFormat } from '@shared/utils';
import { router } from '@inertiajs/vue3';

type StateType = {
    optionEdit: null | object;
    hoursDialog: boolean;
    hoursPeriods: Array<{ start: string; end: string }>;
    selectedDays: SelectedDaysType;
};

const emit = defineEmits(['close', 'refresh']);
defineProps({
    show: Boolean,
});

const alert = useAlert();

const form = useForm({
    status: true,
    lieu_id: null,
    start_at: moment().add(1, 'day').format('yyyy-MM-DD'),
    end_at: moment().add(7, 'day').format('yyyy-MM-DD'),
    days: {},
});

const requestForm = useForm({
    hours_periods: [] as Array<{ start: string; end: string }>,
    comment: '',
});

const state = reactive<StateType>({
    optionEdit: null,
    hoursDialog: false,
    hoursPeriods: [
        {
            start: moment(hours[0], 'HH:mm').format('HH:mm'),
            end: '12:00',
        },
        {
            start: '14:00',
            end: '18:00',
        },
    ],
    selectedDays: {},
});

const onClose = () => {
    emit('close');
};

const getCheckedOptions = computed(() => {
    return (month: moment.Moment) => {
        const daysInMonth = month.daysInMonth();
        let activeDays = 0;
        for (let day = 1; day <= daysInMonth; day++) {
            if (isWithinRange(month, day)) activeDays++;
        }
        const length = Object.keys(state.selectedDays[month.format('yyyy-MM')] || {}).length;

        return {
            checked: activeDays === length,
            indeterminate: !!length && activeDays !== length,
        };
    };
});

const monthsRange = computed(() => {
    const start = moment(form.start_at).startOf('month');
    const end = moment(form.end_at).endOf('month');
    const months = [];

    while (start.isBefore(end) || start.isSame(end, 'month')) {
        months.push(moment(start));
        start.add(1, 'month');
    }

    return months;
});

const isWithinRange = (month: string | moment.Moment, day: number) => {
    const currentDate = moment(month).date(day);
    return currentDate.isBetween(form.start_at, form.end_at, 'day', '[]');
};

const addHoursPeriod = () => {
    state.hoursPeriods.push({
        start: moment(hours[0], 'HH:mm').format('HH:mm'),
        end: moment(hours[hours.length - 1], 'HH:mm').format('HH:mm'),
    });
};

const initializeSelectedDays = () => {
    let start = moment(form.start_at);
    const end = moment(form.end_at);
    state.selectedDays = {};
    console.log('Initializing selected days from', state.hoursPeriods);

    while (start.isBefore(end) || start.isSame(end, 'day')) {
        const monthKey = start.format('YYYY-MM');
        const day = start.date();

        if (!state.selectedDays[monthKey]) {
            state.selectedDays[monthKey] = {};
        }

        const allHours = state.hoursPeriods.reduce<any[]>((acc, period) => {
            return [...acc, ...getHoursByRange(period.start, period.end)];
        }, []);

        state.selectedDays[monthKey][day] = allHours;
        start.add(1, 'day');
    }
};

watch(() => [form.start_at, form.end_at], initializeSelectedDays);
watch(() => state.hoursPeriods, initializeSelectedDays);
onMounted(initializeSelectedDays);

// Toggle individual day selection
const toggleDaySelection = (month: string, day: number) => {
    if (!state.selectedDays[month]) state.selectedDays[month] = {};

    if (state.selectedDays[month][day]) {
        delete state.selectedDays[month][day];
    } else {
        state.selectedDays[month][day] = state.hoursPeriods.reduce<any[]>((acc, period) => {
            return [...acc, ...getHoursByRange(period.start, period.end)];
        }, []);
    }
};

// Toggle selection for the entire month
const toggleMonthSelection = (month: string, checked: boolean) => {
    const daysInMonth = moment(month, 'YYYY-MM').daysInMonth();

    state.selectedDays[month] = {};
    if (!checked) {
        return;
    }

    for (let day = 1; day <= daysInMonth; day++) {
        if (isWithinRange(month, day)) toggleDaySelection(month, day);
    }
};

// Computed property to check if a day is selected
const isDaySelected = (month: string, day: number) => !!state.selectedDays[month]?.[day]?.length;

const handleEditCreneaux = (month: moment.Moment, day: number) => {
    const activeDays = getDaysOfWeekInMonth(state.selectedDays, month, day + 1, form);
    if (!activeDays.length) {
        return alert.show({ type: 'error', title: 'Vous pouvez selectionnée des jours disponible' });
    }
    state.optionEdit = {
        days: activeDays,
        month,
    };
};

const handleSubmit = () => {
    form.transform((data) => {
        data.days = state.selectedDays;
        data.lieu_id = data.lieu_id?.id || data.lieu_id || null;
        return data;
    });
    form.post(route(routes.reservations.settings.storeOrUpdate), {
        preserveScroll: true,
        onSuccess: () => {
            onClose();
            emit('refresh');
        },
        onError: (err) => {
            alert.show({ type: 'error', title: err?.lieu_id || err?.message || 'Une erreur est survenue' });
        },
    });
};



const handleSendRequest = () => {
    console.log('handleSendRequest triggered');

    requestForm.transform((data) => {
        data.hours_periods = state.hoursPeriods.length > 0 ? state.hoursPeriods : null;
        data.comment = requestForm.comment;
        return data;
    });

    requestForm.post(route('reservation-requests.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            console.log('Request submitted successfully');
            alert.show({ type: 'success', title: 'Request sent to admin successfully' });
            requestForm.reset();
            onClose();
            router.visit(route('dashboard'), { preserveState: false });
        },
        onError: (errors) => {
            console.error('Request error:', errors);
            const errorMessage = errors?.hours_periods || errors?.comment || errors?.message || 'Failed to send request';
            alert.show({ type: 'error', title: errorMessage });
        },
    });
};
</script>

<template>
    <div class="contents">
        <Drawer :show="show" mobile title="Planification des disponibilités" @close="$emit('close')" z-index="z-[898]">
            <template #actions>
                <Button link :loading="form.processing" submit @click="handleSubmit">
                    <CheckIcon class="w-5 h-5" />
                    Enregister
                </Button>

            </template>
            <form class="contents" @submit="handleSubmit">
                  <Card padding class="text-md" subtitle="Choisir le lieu et la période concernée">
                        <Card block :separated="false" padding="sm" class="!mb-1">
                            <b> Lieu: </b> <span> {{ form.lieu_id?.name || 'Non Défini' }}</span>
                        </Card>
                        <Card block :separated="false" padding="sm" class="!mb-1">
                            <b> Période: </b>
                            <div class="flex gap-1 flex-wrap">
                                <span class="status-dark rounded px-2 text-xs flex items-center font-bold">
                                    {{ dateFormat(form.start_at, 'letter') }} <ArrowRightIcon class="w-4 mx-1" />
                                    {{ dateFormat(form.end_at, 'letter') }}</span
                                >
                            </div>
                        </Card>
                        <Card block :separated="false" padding="sm" class="!mb-3">
                            <b> Heures: </b>
                            <div class="flex gap-1 flex-wrap">
                                <span
                                    v-for="(p, index) in state.hoursPeriods"
                                    :key="index"
                                    class="status-secondary rounded px-2 text-xs flex items-center"
                                >
                                    {{ p.start }} <ArrowRightIcon class="w-4 mx-1" /> {{ p.end }}
                                </span>
                            </div>
                        </Card>
                        <!-- <Button variant="secondary" full @click="state.hoursDialog = true" :icon="SettingsIcon">
                            Configurer la période et lieu
                        </Button> -->
                    </Card>

                <div class="flex-1">
                    <div v-if="form.start_at && form.end_at" class="p-3">
                        <Card v-for="(month, idx) in monthsRange" :key="idx" block :separated="false">
                            <Disclosure v-slot="{ open }" :default-open="idx === 0">
                                <DisclosureButton
                                    class="flex items-center w-full justify-between rounded-xl px-3 py-2.5 text-left text-md font-medium text-primary focus:outline-none"
                                    as="div"
                                >
                                    <CheckField
                                        :id="`day-${idx}`"
                                        class="!w-fit text-lg font-bold btn-m capitalize"
                                        :label="month.format('MMMM')"
                                        v-bind="getCheckedOptions(month)"
                                        @click.stop="() => {}"
                                        @update="toggleMonthSelection(month.format('YYYY-MM'), $event)"
                                    />
                                    <ChevronUpIcon :class="!open ? 'rotate-180 transform' : ''" class="h-6 w-6 text-gray-400" />
                                </DisclosureButton>

                                <DisclosurePanel class="overflow-clip border-t">
                                    <ul class="grid grid-cols-7 border-b py-2 text-center bg-black text-white">
                                        <li v-for="day in ['L', 'M', 'M', 'J', 'V', 'S', 'D']" :key="day" class="font-bold">
                                            {{ day }}
                                        </li>
                                    </ul>
                                    <ul class="grid grid-cols-7 border-b py-2 text-center bg- mb-2">
                                        <li
                                            v-for="(_, day) in ['L', 'M', 'M', 'J', 'V', 'S', 'D']"
                                            :key="(_, day)"
                                            :class="[
                                                'font-bold',
                                                getDaysOfWeekInMonth(state.selectedDays, month, day + 1, form)?.length
                                                    ? 'text-blue-500'
                                                    : 'text-gray-300',
                                            ]"
                                            @click="handleEditCreneaux(month, day)"
                                        >
                                            <EditIcon class="h-5 w-5 mx-auto" />
                                        </li>
                                    </ul>
                                    <ul class="grid grid-cols-7 gap-1 p-0.5">
                                        <li v-for="n in month.startOf('month').isoWeekday() - 1" :key="'empty-' + n" class="p-2"></li>
                                        <li
                                            v-for="day in Array.from({ length: month.daysInMonth() }, (_, i) => i + 1)"
                                            :key="month.format('YYYY-MM') + '-' + day"
                                            :class="[
                                                'p-2 rounded-xl flex-center font-bold cursor-pointer',
                                                isWithinRange(month, day)
                                                    ? 'text-primary'
                                                    : 'bg-gray-50 text-gray-300 cursor-not-allowed',
                                                isDaySelected(month.format('YYYY-MM'), day) ? 'bg-primary !text-white' : '',
                                            ]"
                                            @click="isWithinRange(month, day) && toggleDaySelection(month.format('YYYY-MM'), day)"
                                        >
                                            {{ day }}
                                        </li>
                                    </ul>
                                </DisclosurePanel>
                            </Disclosure>
                        </Card>
                    </div>
                      <Card class="p-3 bg-gray-50 rounded-lg mt-4">
                    <label for="request-comment" class="text-sm font-medium text-gray-700">Commentaire pour l'admin:</label>
                    <textarea
                        id="request-comment"
                        v-model="requestForm.comment"
                        class="mt-1 p-2 w-full border rounded text-sm text-gray-600"
                        rows="4"
                        placeholder="Ajouter un commentaire pour l'admin (optionnel)"
                    ></textarea>
                </Card>
                <Button link :loading="requestForm.processing" variant="secondary" @click="handleSendRequest">
                    <ArrowRightIcon class="w-5 h-5" />
                    Send Request to Admin
                </Button>
                </div>
            </form>
        </Drawer>
        <OptionsEditModal :item="state" @close="state.optionEdit = null" @update="state.selectedDays[$event.month] = $event.days" />
        <HoursPeriodFilterDialog v-model:show="state.hoursDialog" v-model:hours="state.hoursPeriods" v-model:form="form" />
    </div>
</template>
