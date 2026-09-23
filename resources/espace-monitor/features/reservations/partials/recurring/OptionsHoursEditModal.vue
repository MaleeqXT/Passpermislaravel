<script setup>
import { Card, Dialog, RadioField } from '@shared/components';
import { computed } from 'vue';
import moment from 'moment-timezone';

const emit = defineEmits(['close', 'update']);
const props = defineProps({
    show: Boolean,
    selectedHour: Object,
});

const getValue = (h, i) =>
    moment(h, 'HH:mm')
        .add(i * 15, 'minutes')
        .format('HH:mm');

const getHourOptions = computed(() => {
    const hour = parseInt(props.selectedHour.start_at);
    if (!hour) return [];
    return Array.from({ length: 4 }, (_, i) => {
        return {
            name: getValue(hour, i) + ' à ' + getValue(hour + 1, i),
            start_at: getValue(hour, i),
            end_at: getValue(hour + 1, i),
        };
    });
});

const handleChangeCreneaux = ({ start_at, end_at }) => {
    emit('update', { start_at, end_at, day: props.selectedHour.day });
};
</script>

<template>
    <div class="contents">
        <Dialog
            :show="show"
            mobile
            title="Option du créneau"
            custom-class="!h-fit"
            z-index="z-[1000]"
            subtitle="Changer le debut du créneau pour tous les jours ou pour un jour spécifique"
            @close="$emit('close')"
        >
            <Card title="Début du créneau" as="ul" padding>
                <RadioField
                    :model-value="props.selectedHour.start_at"
                    class="mt-3"
                    :items="getHourOptions"
                    :keys="['name', 'start_at']"
                    @change:full="handleChangeCreneaux"
                />
            </Card>
        </Dialog>
    </div>
</template>
