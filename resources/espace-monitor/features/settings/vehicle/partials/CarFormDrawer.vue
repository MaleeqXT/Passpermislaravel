<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Card, Drawer, ColorField, DateField, Switch, InputField, DialogConfirm } from '@shared/components';
import { routes } from '@espace-monitor/routes';
import DocumentVehicle from './DocumentVehicle.vue';
import { CarType } from '@common/types';
import { computed, watch, reactive } from 'vue';
import { MediaType } from '@shared/types';
import Button from '@shared/components/actions/Button.vue';

type PropsType = {
    item?: CarType | null;
};
type FormType = {
    media_assurance: MediaType[];
    media_carte: MediaType[];
    marque: string;
    modele: string;
    color: string;
    immatriculation: string;
    date_achat?: string;
    date_assurance?: string;
    date_control_tech?: string;
    is_auto: boolean;
};
const emit = defineEmits(['close']);
const props = withDefaults(defineProps<PropsType>(), {
    item: () => ({} as CarType),
});

const isEdit = computed(() => !!props.item?.id);
const data = (item: CarType | null) => ({
    media_assurance: item?.assurance?.media || [],
    media_carte: item?.gray_car_cart?.media || [],
    marque: item?.marque || '',
    modele: item?.modele || '',
    color: item?.color || '#000000',
    immatriculation: item?.immatriculation || '',
    date_achat: item?.date_achat,
    date_assurance: item?.date_assurance,
    date_control_tech: item?.date_control_tech,
    is_auto: item?.is_auto || false,
});
const form = useForm<FormType>(data(props.item));
watch(props, ({ item }) => {
    Object.assign(form, data(item));
    form.defaults();
    form.clearErrors();
});
const state = reactive({
    confirmation: false,
});
const onSubmit = () => {
    form.transform((d) => ({
        ...d,
        media_carte: d.media_carte.map((item) => item?.storage_media?.id),
        media_assurance: d.media_assurance.map((item) => item?.storage_media?.id),
        marque: d.marque,
    }));
    if (isEdit.value) {
        return form.put(route(routes.settings.cars.update, props.item?.id), { onSuccess: onClose });
    }
    form.post(route(routes.settings.cars.store), { onSuccess: onClose });
};
const onClose = () => {
    form.reset();
    state.confirmation = false;
    emit('close');
};
</script>

<template>
    <Drawer :show="!!item" :title="item ? 'Documents du véhicule ' : 'Information sur le véhicule'" @close="onClose">
        <template v-if="item?.id" #actions>
            <Button variant="danger" link @click="state.confirmation = true">Supprimer</Button>
        </template>
        <form class="flex flex-col h-full" @submit.prevent="onSubmit">
            <Card padding="none" class="flex-1 px-2 pt-1">
                <div class="grid gap-5 relative">
                    <InputField v-model="form.marque" :error="form.errors.marque" label="Marque" />
                    <InputField v-model="form.modele" :error="form.errors.modele" label="Modèle" />
                    <InputField v-model="form.immatriculation" :error="form.errors.immatriculation" label="Immatriculation" />
                    <DateField
                        v-model="form.date_achat"
                        :error="form.errors.date_achat"
                        label="Date de 1er immatriculation"
                        min-date="2000-01-01"
                    />
                    <DateField v-model="form.date_assurance" :error="form.errors.date_assurance" label="Date de validité assurance" />
                    <DateField v-model="form.date_control_tech" :error="form.errors.date_control_tech" label="Date contrôle technique" />
                    <ColorField v-model="form.color" :error="form.errors.color" label="Couleur" class="h-fit flex-none" />
                    <Switch v-model="form.is_auto" :error="form.errors.is_auto" label="Boite Automatique" />
                </div>
                <template v-if="isEdit">
                    <DocumentVehicle :values="form" :item="item" />
                </template>
            </Card>
            <div class="sticky bottom-0 flex justify-end gap-2 px-3 py-2 bg-white/70 backdrop-blur shadow-up">
                <Button submit full variant="primary" :loading="form.processing" :disabled="!form.isDirty">Enregistrer</Button>
            </div>
        </form>
        <DialogConfirm v-if="item?.id" :show="state.confirmation" :href="route(routes.settings.cars.delete, item.id)" @close="onClose" />
    </Drawer>
</template>
