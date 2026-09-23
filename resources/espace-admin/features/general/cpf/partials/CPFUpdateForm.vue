<script setup lang="ts">
import { Select, InputField, ButtonGroup, DateField } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ItemImage } from '@common/components';
import { CPFStatus } from '@common/enums';
import moment from 'moment-timezone';
import { XIcon } from '@adersolutions/icons';
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
    date_verif: props.data?.date_verif || '',
    comment: props.data?.comment || '',
    status: Number(props.data?.status) || '',
});
const submit = () => {
    form.put(route(routes.cpf.update, props.data.id), {
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
        label: 'Enregister CPF',
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
</script>

<template>
    <form class="flex flex-col h-full" @submit.prevent="submit">
        <article class="px-4 py-3 bg-white shadow-down">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold">Modification de CPFs</h2>
                <XIcon class="h-7 w-7 p-0.5 text-gray-500 cursor-pointer btn-m" @click="onClose" />
            </div>
            <small>{{ `Créé le ${moment(data.created_at).format('DD/MM/YYYY')}` }}</small>
        </article>
        <article v-if="data.student?.user" class="px-4 pt-3 bg -white">
            <ItemImage
                is-mobile
                class="bg-slate-200 p-2 rounded-xl"
                :title="data.student.user.name"
                :src="data.student.user.media || data.student.user.profile_photo_url"
                :content="data.student.user.email"
            />
        </article>
        <article class="flex-1 p-4 flex flex-col gap-5">
            <Select
                v-model="form.status"
                :items="Object.values(CPFStatus)"
                :error="form.errors.status"
                :show-search="false"
                :keys="['name', 'id']"
                class="w-full"
                label="Statut"
                placeholder="Choisir une statut"
            />

            <DateField v-model="form.date_verif" :error="form.errors.date_verif" label="Date verification" />
            <div>
                <InputField v-model="form.comment" :multiline="5" label="Commentaire" :errors="form.errors.comment" />
            </div>
        </article>
        <ButtonGroup :actions="actions" class="px-4 py-3 bg-white shadow-up" />
    </form>
</template>
