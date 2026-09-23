<script setup lang="ts">
import { PageMobile } from '@common/components';
import { Card, DateField, Select, FileLibrary, InputField, Thumb } from '@shared/components';
import { ChevronRightIcon, NoteIcon } from '@adersolutions/icons';
import { useForm } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import { routes } from '@espace-student/routes';
import { useFileLibrary } from '@shared/stores';
import type { UserType } from '@common/types';
import { SizeEnum } from '@shared/enums';

const props = defineProps<{ user: UserType }>();
const fl = useFileLibrary();

const form = useForm({
    first_name: props.user.first_name || '',
    last_name: props.user.last_name || '',
    email: props.user.email || '',
    adresse: props.user.adresse || '',
    phone: props.user.phone || '',
    media: props.user.media || '',
    sexe: props.user.sexe || '',
    date_naissance: props.user.date_naissance || '',
    postal: props.user.postal || '',
    ville: props.user.ville || '',
    is_cpf: props.user.student?.is_cpf || 0,

    ville1: '',
    postal1: '',

    password: '',
    password_confirmation: '',
});
const tabs = [
    { name: 'Info personnelles', id: 'info' },
    { name: 'Autres', id: 'autres' },
];

const state = reactive({
    tab: tabs[0].id,
});

const actions = computed(() => [
    {
        label: 'Enregistrer',
        variant: 'warning',
        full: true,
        disabled: !form.isDirty,
        loading: form.processing,
        onAction: onSubmit,
    },
]);

const onAddPhoto = (value) => {
    form.media = value?.path;
};
const onSubmit = () => {
    form.put(route(routes.settings.profile.update, props.user.student?.id), {
        preserveScroll: true,
        onSuccess: () => {},
    });
};

// Watcher to update postal based on ville
watch(
    () => form.ville,
    (newVille) => {
        if (newVille === 'Creil') {
            form.postal = '60100';
        } else if (newVille === 'Toulouse') {
            form.postal = '31300';
        } else {
            form.postal = ''; // Clear postal if ville is invalid
        }
    }
);
</script>

<template>
    <PageMobile title="Profile" :width="SizeEnum.MD" slided :actions="actions" back>
        <FileLibrary menu @submit="onAddPhoto" />

        <template #header>
            <dd class="flex relative gap-3 items-center p-1 rounded-lg bg-dark-block m-3" @click="fl.open()">
                <div class="relative">
                    <Thumb :src="form.media" />
                </div>
                <b v-if="user.name">{{ user.name }}</b>
                <button class="btn btn-link ml-auto">Changer</button>
            </dd>
        </template>
        <!-- <template #sticky>
            <div class="p-1 bg-gray-100">
                <TabSwitch v-if="!user.student?.contract_path" v-model="state.tab" :items="tabs" full class="!bg-gray-100" />
            </div>
        </template> -->
        <div class="my-6 max-w-xl mx-auto w-full">
            <Card v-if="state.tab === tabs[1].id && user.student?.contract_path" block padding>
                <ul>
                    <li>
                        <a :href="user.student.contract_path" target="_blank" class="flex gap-2" rel="noopener noreferrer">
                            <span class="flex-1 flex items-center gap-2">
                                <NoteIcon class="w-6 h-6 text-slate-600" />
                                Contract
                            </span>
                            <ChevronRightIcon class="w-6 h-6 text-slate-600" />
                        </a>
                    </li>
                </ul>
            </Card>
            <template v-else>
                <Card title="Informations personnelles" class="flex flex-col flex-1">
                    <div class="flex gap-2">
                        <InputField v-model="form.first_name" :error="form.errors.first_name" label="Prénom" />
                        <InputField v-model="form.last_name" :error="form.errors.last_name" label="Nom" />
                    </div>

                    <InputField v-model="form.email" :error="form.errors.email" label="Email" />
                    <Select
                        v-model="form.sexe"
                        label="Genre"
                        :show-search="false"
                        :items="[
                            { id: 'Homme', name: 'Homme' },
                            { id: 'Femme', name: 'Femme' },
                        ]"
                        :error="form.errors.sexe"
                        placeholder="Genre"
                    />
                    <InputField
                        v-model="form.phone"
                        :error="form.errors.phone"
                        label="Numéro de téléphone"
                        type="tel"
                        mask="## ## ## ## ##"
                    />

                    <DateField v-model="form.date_naissance" :error="form.errors.date_naissance" label="Date de naissance" birthday />
                </Card>
                <Card title="Adresse" class="mt-7 flex flex-col flex-1">
                    <InputField
                        v-model="form.ville1"
                        :error="form.errors.ville1"
                        label="Ville 1"
                        required
                    />
                    <InputField
                        v-model="form.postal1"
                        :error="form.errors.postal1"
                        label="Postal Code 1"
                        required
                        type="text"
                        mask="#####"
                    />
                    <Select
                        v-model="form.ville"
                        :error="form.errors.ville"
                        label="Agency"
                        :show-search="false"
                        :items="[
                            { id: 'Creil', name: 'Creil' },
                            { id: 'Toulouse', name: 'Toulouse' },
                        ]"
                        placeholder="Agency"
                    />
                    <InputField
                        v-model="form.postal"
                        :error="form.errors.postal"
                        label="Postal"
                        placeholder="Postal"
                        readonly
                        class="hidden"
                    />

                    <InputField v-model="form.adresse" :multiline="3" :error="form.errors.adresse" label="Adresse" />

                </Card>
                <Card title="Changement de mot de passe" class="mt-5 flex flex-col">
                    <InputField
                        id="password"
                        v-model="form.password"
                        :error="form.errors.password"
                        label="Mot de passe"
                        required
                        type="password"
                    />
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
            </template>
        </div>
    </PageMobile>
</template>
