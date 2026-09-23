<script setup lang="ts">
import { PageMobile } from '@common/components';
import { Card, DateField, FileLibrary, InputField, Thumb } from '@shared/components';
import { PlusCircleIcon } from '@adersolutions/icons';
import { useForm } from '@inertiajs/vue3';
import { GeneralStatusEnum } from '@common/enums';
import { computed } from 'vue';
import { routes } from '@espace-monitor/routes';
import { useFileLibrary } from '@shared/stores';
import type { UserType } from '@common/types';
import { SizeEnum } from '@shared/enums';
import { checkPassword } from '@shared/utils';

type PropsType = {
    user: NonNullable<UserType>;
};
const props = defineProps<PropsType>();
const fl = useFileLibrary();

const form = useForm({
    first_name: props.user.first_name || '',
    last_name: props.user.last_name || '',
    email: props.user.email || '',
    status: props.user.status || GeneralStatusEnum.INACTIVE,
    adresse: props.user.adresse || '',
    phone: props.user.phone || '',
    media: props.user.media || '',
    sexe: props.user.sexe || '',
    date_naissance: props.user.date_naissance || '',
    postal: props.user.postal || '',
    ville: props.user.ville || '',

    password: '',
    password_confirmation: '',

    // account
    iban: props.user?.monitor?.account?.iban || '',
    bic: props.user?.monitor?.account?.bic || '',
});

const actions = computed(() => [
    {
        label: 'Enregistrer',
        variant: 'warning',
        full: true,
        disabled: form.processing || !form.isDirty,
        loading: form.processing,
        onAction: onSubmit,
    },
]);

const onStoreFile = (value) => {
    form.media = value?.path;
};
const onSubmit = () => {
    form.put(route(routes.settings.profile.update, props.user?.monitor?.id), {
        preserveScroll: true,
        onSuccess: () => {},
    });
};
</script>

<template>
    <PageMobile :width="SizeEnum.XS" title="Paramètres" back slided :actions="actions">
        <template #header>
            <dd class="flex relative gap-3 items-center p-1 rounded-lg bg-dark-block m-3" @click="fl.open()">
                <div class="relative">
                    <Thumb :src="form.media" />
                </div>
                <b v-if="user.name">{{ user.name }}</b>
                <button class="btn btn-link ml-auto">Changer</button>
            </dd>
        </template>

        <Card title="Informations personel" class="mt-7 pb-4 flex flex-col px-3 flex-1">
            <div class="flex gap-3">
                <InputField v-model="form.first_name" :error="form.errors.first_name" label="Prénom" />
                <InputField v-model="form.last_name" :error="form.errors.last_name" label="Nom" />
            </div>
            <InputField v-model="form.email" :error="form.errors.email" label="Email" />
            <InputField v-model="form.phone" :error="form.errors.phone" label="Numéro de téléphone" type="tel" mask="XX XX XX XX XX" />

            <InputField v-model="form.adresse" :multiline="3" :error="form.errors.adresse" label="Adresse" />
            <div class="flex gap-3">
                <InputField v-model="form.postal" :error="form.errors.postal" label="Postal" />
                <InputField v-model="form.ville" :error="form.errors.ville" label="Ville" />
            </div>

            <DateField v-model="form.date_naissance" :error="form.errors.date_naissance" birthday label="Date de naissance" />
        </Card>
        <Card title="Bank informations" class="flex flex-col gap-4 px-3 flex-1">
            <InputField v-model="form.iban" mask="AA ## XXXX ###### ########" avoid-replace :error="form.errors.iban" label="IBAN" />
            <InputField v-model="form.bic" :length="11" :error="form.errors.bic" label="BIC" />
        </Card>
        <Card padding title="Changement de mot de passe" class="flex flex-col px-3 flex-1">
            <InputField id="password" v-model="form.password" :error="form.errors.password" label="Mot de passe" required type="password" />
            <InputField
                id="password_confirmation"
                v-model="form.password_confirmation"
                :error="form.errors.password_confirmation"
                label="Mot de passe confirmation"
                required
                type="password"
                @blur="checkPassword"
            />
        </Card>
        <FileLibrary menu @submit="onStoreFile" />
    </PageMobile>
</template>
