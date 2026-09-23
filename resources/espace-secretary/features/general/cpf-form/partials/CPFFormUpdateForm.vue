<script setup lang="ts">
import {ButtonGroup, InputField, RadioField, Select} from '@shared/components';
import {routes} from '@espace-secretary/routes';
import {useForm} from '@inertiajs/vue3';
import {computed} from 'vue';
import {CPFoffres} from "@common/enums";
import {XIcon} from '@adersolutions/icons';
import Button from "@shared/components/actions/Button.vue";

const emit = defineEmits(['close']);
const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
    isEdit: Boolean,
    uuid: String,
});

const form = useForm({
    boite: props.data?.boite || '',
    offre: props.data?.offre || '',
    numero_cpf: props.data?.numero_cpf || '',
    reservations: props.data?.reservations || [{
        date: '',
        houre: '',
        start: '',
        end: '',
    }],
});
const submit = () => {
    form.put(route(routes.formCpf.update, props.data.id), {
        preserveScroll: true,
        onSuccess: onClose,
    });
};
const onClose = () => {
    form.reset();
    emit('close');
};
const actions = computed(() => [
    {
        label: 'Modifier CPF Form',
        variant: 'primary',
        submit: true,
        disabled: form.processing || !form.isDirty,
        loading: form.processing,
        onAction: submit,
    },
    {
        label: 'Rejeter',
        disabled: form.processing,
        variant: 'secondary',
        onAction: onClose,
    },
]);

const boite = {
    manual: {
        id: 'manual',
        name: 'Manuel',
    },
    auto: {
        id: 'auto',
        name: 'Automatique',
    },

}

const addElementInArray = () => {
    form.reservations.push({
        date: '',
        houre: '',
        start: '',
        end: '',
    });
};

const removeReservation = (index) => {
    if (form.reservations.length <= 1) {
        return;
    }
    form.reservations.splice(index, 1);
};

</script>

<template>
    <form class="flex flex-col h-full" @submit.prevent="submit">
        <article class="px-4 py-3 bg-white shadow-down">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold">Modification de CPFs</h2>
                <XIcon class="h-7 w-7 p-0.5 text-gray-500 cursor-pointer btn-m" @click="onClose"/>
            </div>
        </article>

        <article class="flex flex-1 p-4 flex flex-col gap-5">
            <div>
            <InputField
                v-model="form.numero_cpf"
                label="Numero de dossier cpf"
                :error="form.errors.numero_cpf"
                type="text"
                anme="numero_cpf"
                autocomplete="Rservation"
                class="mt-1 block w-full flex-10"
            />
            </div>
            <label class="text-sm primary font-semibold mt-2">Boite Type</label>
            <RadioField :error="form.errors.boite" class="text-xs" v-model:full="form.boite" :items="boite"/>
            <Select
                v-if="form.boite"
                v-model="form.offre"
                label="Offre"
                :items="Object.values(CPFoffres[form.boite.id])"
                :show-search="true"
                ssr
                mobile
                clear
                class="w-full"
                placeholder="Choisir un offre"

            />

            <div v-for="(reservation,key) in form.reservations"
                 class="gap-2 flex flex-col">
                <div class="flex justify-between">
                    <label class="text-sm primary  font-semibold mt-2 ">
                        Reservation {{ key + 1 }}
                    </label>
                    <Button
                        label="Supprimer"
                        variant="secondary"
                        @click="removeReservation(key)"
                        class="flex-center"
                    >
                        Supprimer
                    </Button>
                </div>

                <InputField
                    v-model="form.reservations[key].date"
                    label="Date"
                    :error="form.errors.houres"
                    type="Date"
                    anme="Date"
                    autocomplete="Rservation"
                    class="mt-1 block w-full"
                />
                <InputField
                    v-model="form.reservations[key].houre"
                    label="Durée"
                    :error="form.errors.houres"
                    type="number"
                    anme="Rservation"
                    autocomplete="Rservation"
                    class="mt-1 block w-full"
                />
                <InputField
                    v-model="form.reservations[key].start"
                    label="strat"
                    :error="form.errors.houres"
                    type="time"
                    anme="Rservation"
                    autocomplete="Rservation"
                    class="mt-1 block w-full"
                />
                <InputField
                    v-model="form.reservations[key].end"
                    label="fin"
                    :error="form.errors.houre"
                    type="time"
                    anme="Rservation"
                    autocomplete="Rservation"
                    class="mt-1 block w-full"
                />

            </div>
            <div class="flex justify-end gap-2">
                <Button
                    label="Ajouter"
                    variant="primary"
                    @click="addElementInArray()"
                    class=" flex-center"
                >
                    Ajouter
                </Button>
                <Button
                    label="Supprimer"
                    variant="secondary"
                    @click="form.reservations.splice(1)"
                    class="flex-center"
                >
                    Supprimer
                </Button>

            </div>


        </article>


        <ButtonGroup :actions="actions" class="px-4 py-3 bg-white shadow-up"/>
    </form>
</template>
