<script setup lang="ts">
import { watch, computed } from 'vue';
import { Switch, InputField, Drawer, Button, Card } from '@shared/components';
import { DialogPageAction } from '@espace-monitor/components';
import { useMutation } from '@shared/hooks';
import { dateFormat } from '@shared/utils';
import { routes } from '@espace-monitor/routes';
import { CalendarIcon, ClockIcon } from '@adersolutions/icons';

const emit = defineEmits(['close', 'refresh']);
const props = defineProps({
    item: [Object, null],
});

// comment
// estimation

const form = useMutation({
    comment: props.item?.comment || '',
    estimation: props.item?.estimation || null,
    is_absent: props.item?.is_absent || false,
});
const reservation = computed(() => props.item?.reservation || {});
watch(props, ({ item }) => {
    form.comment = item?.comment || '';
    form.estimation = item?.estimation || null;
    form.is_absent = item?.is_absent || false;
});
const onSubmit = () => {
    if (props.item?.id) {
        form.put(route(routes.api.reviews.update, props.item.id)).then(() => {
            emit('close');
            emit('refresh');
        });
        return;
    }
    form.post(route(routes.api.reviews.store, reservation.value.id)).then(() => {
        emit('close');
        emit('refresh');
    });
};
</script>

<template>
    <Drawer :show="!!item" title="Avis" @close="$emit('close')">
        <div class="flex-1 px-3">
            <Card title="Info du Séance">
                <div class="box bg-white divide-y divide-slate-200 text-md mb-3 mt-1">
                    <dl class="grid grid-cols-3 py-2 px-3">
                        <dd class="opacity-70 flex gap-2 items-center">
                            <CalendarIcon class="w-4" />
                            Date
                        </dd>
                        <dd class="col-span-2 font-semibold">
                            <span class="mr-2">:</span>

                            {{ dateFormat(reservation.date, 'full') }}
                        </dd>
                    </dl>
                    <dl class="grid grid-cols-3 py-2 px-3">
                        <dd class="opacity-70 flex gap-2 items-center">
                            <ClockIcon class="w-4" />
                            Heure
                        </dd>
                        <dd class="col-span-2 font-semibold">
                            <span class="mr-2">:</span>

                            {{ reservation.start_at }} à
                            {{ reservation.end_at }}
                        </dd>
                    </dl>
                </div>
            </Card>

            <div class="flex flex-col gap-3">
                <Switch v-model="form.is_absent" :errors="form.errors.is_absent" label="Absent" />

                <InputField v-model="form.comment" label="avis " :multiline="3" :error="form.errors.comment" />
                <InputField
                    v-if="item?.is_estimated"
                    v-model="form.estimation"
                    label="Estimez les heures de votre Séance"
                    type="number"
                    :error="form.errors.estimation"
                />
            </div>
        </div>
        <div class="page-actions">
            <Button variant="primary" full :loading="form.processing" :disabled="!form.isDirty" @click="onSubmit"> Enregistrer </Button>
        </div>
    </Drawer>
</template>
