<script setup lang="ts">
import { Card, DateField, Errors, InfosList, Dialog, Button } from '@shared/components';
import { computed } from 'vue';
import moment from 'moment-timezone';
import { useAlert } from '@shared/stores';
import { hours } from '@common/enums';
import { ArrowRightIcon, DeleteIcon, PlusIcon } from '@adersolutions/icons';
import { AreaSelectionDialog } from '@common/components';

interface HoursPeriod {
    start: string;
    end: string;
}
const show = defineModel('show', {
    type: Boolean,
    default: false,
});
const hoursPeriods = defineModel<HoursPeriod[]>('hours', {
    type: Array,
    default: () => [],
});
const form = defineModel('form', {
    type: Object,
    default: () => ({
        status: true,
        lieu_id: null,
        start_at: null,
        end_at: null,
        errors: {},
    }),
});

const alert = useAlert();

const onClose = () => {
    show.value = false;
};
const isLastHour = computed(() => {
    return hoursPeriods.value.length > 0 && hoursPeriods.value[hoursPeriods.value.length - 1].end === '23:00';
});
const addHoursPeriod = () => {
    const lastHour = hoursPeriods.value[hoursPeriods.value.length - 1];
    hoursPeriods.value = [
        ...hoursPeriods.value,
        {
            start: moment(lastHour.end, 'HH:mm').add(1, 'hour').format('HH:mm'),
            end: moment(hours[hours.length - 1], 'HH:mm')
                .add(2, 'hour')
                .format('HH:mm'),
        },
    ];
};

const removeHoursPeriod = (index: number) => {
    if (hoursPeriods.value.length === 0) {
        return alert.show({
            title: 'Aucune période de planification',
            content: 'Vous devez ajouter au moins une période de planification des disponibilités.',
            type: 'error',
        });
    }
    hoursPeriods.value = hoursPeriods.value.filter((_, i) => i !== index);
};

const updateHoursPeriod = (index: number, { start, end }: HoursPeriod) => {
    hoursPeriods.value[index] = { start, end };
};
</script>

<template>
    <Dialog :show="show" mobile title="Periods configuration" @close="onClose" z-index="z-[899]">
        <div class="flex-1">
            <Card class="p-3 grid" separated>
                <AreaSelectionDialog form @change="form.lieu_id = $event.place || null" />
                <Errors v-if="form.errors.lieu_id" :errors="form.errors.lieu_id" />

                <div class="text-sm">Choisir la période de planification des disponibilités.</div>
                <div class="bg-slate-100 rounded-lg p-2">
                    <p class="text-md font-semibold mb-2">Période de planification des disponibilités.</p>
                    <div class="flex-center gap-1">
                        <DateField
                            label="Date de début"
                            filter
                            v-model="form.start_at"
                            :min-date="moment().add(1, 'day').format('yyyy-MM-DD')"
                            class="flex-1"
                            @change="form.end_at = null"
                        />
                        <ArrowRightIcon class="w-6 h-6 text-gray-700" />
                        <DateField
                            label="Date de fin"
                            :key="form.end_at"
                            v-model="form.end_at"
                            :min-date="moment(form.start_at).add(1, 'd').toDate()"
                            class="flex-1"
                        />
                    </div>
                </div>
                <div class="space-y-3 bg-slate-100 rounded-lg p-2">
                    <p class="text-md font-semibold mb-2">Heures de planification des disponibilités</p>
                    <div v-for="(period, index) in hoursPeriods" :key="index" class="flex-center gap-1">
                        <DateField
                            label="Heure de début"
                            v-model="period.start"
                            :min-time="moment(period.start, 'HH:mm').subtract(1, 'hour').format('HH:mm')"
                            :max-time="moment(period.end, 'HH:mm').subtract(1, 'hour').format('HH:mm')"
                            class="flex-1"
                            time
                            @change="updateHoursPeriod(index, { ...period, start: $event })"
                        />
                        <ArrowRightIcon class="w-6 h-6 text-gray-700" />
                        <DateField
                            label="Heure de fin"
                            v-model="period.end"
                            :min-time="moment(period.end, 'HH:mm').add(1, 'hour').format('HH:mm')"
                            max-time="23:00"
                            time
                            class="flex-1"
                            @change="updateHoursPeriod(index, { ...period, end: $event })"
                        />
                        <Button v-if="hoursPeriods.length > 1" variant="danger" link :icon="DeleteIcon" @click="removeHoursPeriod(index)" />
                    </div>
                    <Button
                        v-if="hoursPeriods.length < 5"
                        :disabled="isLastHour"
                        variant="success"
                        full
                        :icon="PlusIcon"
                        @click="addHoursPeriod"
                    >
                        Ajouter une période <span v-if="isLastHour" class="text-red-500/70">(Dernière heures est 23:00)</span>
                    </Button>
                    <InfosList
                        :items="[
                            'Les heures de planification sont utilisées pour définir les créneaux disponibles pour les réservations.',
                            'Vous pouvez ajouter jusqu’à 5 périodes de planification.',
                        ]"
                    />

                    <Errors errors="Changement d'heures peut modifier les disponibilités déjà existante" />
                </div>
            </Card>
        </div>
        <div class="page-actions">
            <Button variant="secondary" full @click="onClose"> Fermer </Button>
        </div>
    </Dialog>
</template>
