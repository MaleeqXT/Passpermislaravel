<script setup lang="ts">
import { GeneralStatusEnum } from '@common/enums';
import { Page } from '@shared/components';
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Card,
    DateField,
    Select,
    FileLibrary,
    SingleImageField,
    TabSwitch,
    InputField,
    EmptyState,
    Back,
    Alerts,
    Button,
    Switch,
} from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useApp } from '@shared/stores';
import { GeneralStatus } from '@common/enums';
import { NoteIcon, AdjustIcon } from '@adersolutions/icons';
import { ButtonGroup } from '@shared/components';
import { useFiles } from '@shared/hooks';
import { ButtonType } from '@shared/types';



type PropsType = {
    secretary: any;
};
const props = defineProps<PropsType>();

// ✅ Full secretary form
const form = useForm({
    first_name: props.secretary.user?.first_name || '',
    last_name: props.secretary.user?.last_name || '',
    email: props.secretary.user?.email || '',   // ✅ Add this line
    phone: props.secretary.user?.phone || '',
    sexe: props.secretary.user?.sexe || '',
    date_naissance: props.secretary.user?.date_naissance || '',
    ville: props.secretary.user?.ville || '',
    postal: props.secretary.user?.postal || '',
    adresse: props.secretary.user?.adresse || '',
    neph: props.secretary.user?.neph || '',
    date_code: props.secretary.user?.date_code || '',
    status: props.secretary.status || GeneralStatusEnum.ACTIVE,
    password: '',
    password_confirmation: '',
    media: props.secretary.user?.media || null,
    is_cpf: props.secretary.user?.is_cpf || false,
    boite_type: props.secretary.user?.boite_type || '',
});

// ✅ Submit directly to your Laravel route (no Ziggy)
const submit = () => {
    form.put(`/secretaries/${props.secretary.id}`);
};

// 🛠 missing placeholders to prevent errors
const actions = [
    { label: 'Enregistrer', variant: 'success', onAction: submit },
];
const availableBoiteTypes = [
    { id: 'Auto', name: 'Boite Auto' },
    { id: 'Manuelle', name: 'Boite Manuelle' },
];
const onDeleteFile = () => {
    form.media = null;
};
const checkPassword = () => {
    if (form.password && form.password !== form.password_confirmation) {
        form.setError('password_confirmation', 'Les mots de passe ne correspondent pas');
    }
};
const heading = props.isEdit ? 'Modifier Sectory' : 'Nouveau Secretary';

</script>

<template>
    <div>
        <FileLibrary />
        <form class="form-page" @submit.prevent="submit">
            <FileLibrary @submit="form.media = $event" />
            <article class="left-form">
                     <Back :title="heading" :back="route('secretaries.index')" />

                <div class="form-content">
                    <Alerts />
                    <Card block padding title="Géneral infos">
                        <div class="grid md:grid-cols-2 gap-4">
                            <InputField
                                id="firstName"
                                v-model="form.first_name"
                                :error="form.errors.first_name"
                                label="Prénom"
                                class="flex-1"
                                required
                            />
                            <InputField
                                id="lastName"
                                v-model="form.last_name"
                                :error="form.errors.last_name"
                                label="Nom"
                                class="flex-1"
                                required
                            />
                            <InputField id="email" v-model="form.email" :error="form.errors.email" label="Email" required type="email" />

                            <InputField
                                id="tel"
                                v-model="form.phone"
                                :error="form.errors.phone"
                                label="Telephone"
                                type="tel"
                                mask="## ## ## ## ##"
                                :length="10"
                                required
                            />
                            <Select
                                v-model="form.sexe"
                                label="Genre"
                                :show-search="false"
                                :items="[
                                    { id: 'Homme', name: 'Homme' },
                                    { id: 'Femme', name: 'Femme' },
                                ]"
                                :error="form.errors.sexe"
                                class="w-full"
                                placeholder="Genre"
                            />
                            <DateField
                                v-model="form.date_naissance"
                                :error="form.errors.date_naissance"
                                birthday
                                label="Date de naissence"
                            />
                            <InputField id="ville" v-model="form.ville" class="flex-1" :error="form.errors.ville" label="Ville" required />
                            <InputField
                                id="postal"
                                v-model="form.postal"
                                :error="form.errors.postal"
                                label="Code postal"
                                class="flex-1"
                                required
                                type="number"
                            />
                        </div>
                        <InputField
                            id="adress"
                            v-model="form.adresse"
                            :error="form.errors.adresse"
                            label="Adresse"
                            :multiline="2"
                            required
                        />
                    </Card>
                    <Card block padding title="Balance">
                        <div class="grid sm:grid-cols-2 gap-4">
                            <InputField
                                id="neph"
                                v-model="form.neph"
                                :error="form.errors.neph"
                                label="Neph"
                                mask="### ### ### ###"
                                required
                            />

                            <DateField v-model="form.date_code" :error="form.errors.date_code" label="Date de code" />
                        </div>
                        <div class="pt-4 md:grid md:grid-cols-2 md:gap-4">
                            <div>
                                <dt class="text-sm/6 font-medium text-gray-900 flex items-center justify-between">
                                    <span> Balance Disponible </span>
                                    <!-- <Button link info :icon="AdjustIcon" @click="selectedBalance = true"> Modifier </Button> -->
                                </dt>
                            </div>
                        </div>
                    </Card>
                    <Card block padding title="Changement de mot de passe">
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
                </div>

                <ButtonGroup vertical class="form-actions" :actions="actions" />
            </article>
           <article class="right-form">
    <div>
        <Select
            v-model="form.status"
            :error="form.errors.status"
            :show-search="false"
            label="Statut"
            :items="Object.values(GeneralStatus)"
            class="w-full"
            placeholder="Statut"
        />

        <Card block>
            <SingleImageField
                :src="form.media?.path || form.media || null"
                :error="form.errors.media"
                @change="form.media = $event"
                @delete="onDeleteFile"
            />
        </Card>



        <div class="flex items-center justify-between mt-6">
            <label class="block font-medium text-xs text-gray-600 mb-0.5 mt-1">Cpf</label>
            <Switch v-model="form.is_cpf" class="text-xs bg-gray-200" size="sm" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <Select
                v-model="form.boite_type"
                :items="availableBoiteTypes"
                option-value-key="id"
                option-label-key="name"
                option-disabled-key="disabled"
                label="Type de boite"
                class="w-full"
            />
        </div>


    </div>
     <div class="">
            <Button variant="success" @click="submit">Modifier Secrétaire</Button>
        </div>
</article>

        </form>
    </div>
</template>
