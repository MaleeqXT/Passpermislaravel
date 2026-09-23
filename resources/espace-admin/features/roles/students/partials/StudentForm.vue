<script setup lang="ts">
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
import AddBalanceDrawer from '../views/partials/modals/AddBalanceDrawer.vue';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
    actions: Array,
    isEdit: Boolean,
    uuid: String,
    studentContract: Object,
    // Receive adminMode as a prop (true = BA, false = BM)
    adminMode: {
        type: Boolean,
        required: true,
    },
});

const form = useForm({
    first_name: props.data.first_name || '',
    last_name: props.data.last_name || '',
    email: props.data.email || '',
    status: props.data.status || '1',
    adresse: props.data.adresse || '',
    neph: props.data.student?.neph || '',
    is_cpf: props.data.student?.is_cpf || 0,
    boite_type: props.data.student?.boite_type ?? (props.adminMode ? '1' : '0'),
    date_code: props.data.student?.date_code || '',
    phone: props.data.phone || '',
    media: props.data?.media || props.data?.profile_photo_url || null,
    sexe: props.data.sexe || null,
    date_naissance: props.data.date_naissance || null,
    postal: props.data.postal || '',
    ville: props.data.ville || '',
    password: props.data.password || '',
    password_confirmation: props.data.password_confirmation || '',
    role: props.data.role || 2,
});

const allBoiteOptions = [
    { id: '0', name: 'BM' },
    { id: '1', name: 'BA' },
];

const availableBoiteTypes = computed(() => {
    const studentChoice = props.data.student?.boite_type; // agar student edit ho raha hai
    const adminChoice = props.adminBoiteType; // admin ka select kia hua option (BM ya BA)

    if (studentChoice !== null && studentChoice !== undefined) {
        // 🔹 Old Student → sirf uska saved option show hoga
        return allBoiteOptions.filter((opt) => opt.id === studentChoice);
    }

    // 🔹 New Student → dono show karo, lekin sirf admin choice enable
    return allBoiteOptions.map((opt) => ({
        ...opt,
        disabled: opt.id !== adminChoice,
    }));
});

// ensure form.boite_type matches adminMode
watch(
    () => props.adminMode,
    (newVal) => {
        if (!props.data.student?.boite_type) {
            form.boite_type = newVal ? '1' : '0';
        }
    }
);

const medias = useFiles();
const page = useApp();
const selectedBalance = ref(false);


const submit = () => {
    form.transform((data) => {
        if (data.media?.id) {
            data.media = data.media.path;
        }
        return data;
    });
    if (props.isEdit) {
        form.put(route(routes.users.students.update, props.data?.student?.id));
    } else {
        form.post(route(routes.users.students.store));
    }
};

const checkPassword = () => {
    if (form.password !== form.password_confirmation) {
        form.errors.password_confirmation = "le mot de passe n'est pas confirmé";
    } else {
        form.errors.password_confirmation = '';
    }
};

const onDeleteFile = () => {
    if (props.isEdit && props.data.media?.id) {
        medias.delete(props.data.media.id).then(() => {
            form.media = null;
        });
    } else {
        form.media = null;
    }
};

const actions = computed<ButtonType[]>(() => [
    {
        label: `${props.isEdit ? 'Modifier' : 'Ajouter'} Condidat`,
        variant: 'primary',
        disabled: !form.isDirty,
        loading: form.processing,
        submit: true,
        full: true,
        onAction: submit,
    },
    {
        label: 'Abandonner',
        disabled: form.processing || !form.isDirty,
        variant: 'secondary',
        full: true,
        onAction: () => form.reset(),
    },
]);

const currentBalance = ref(props.data.student?.balance);

const setBalance = (balance) => {
    currentBalance.value = balance;
};

watch(form, (value) => {
    if (!props.uuid) {
        return;
    }
    const data = value.data();
    data.id = props.uuid;
    data.user = {
        profile_photo_url: page.user?.profile_photo_url,
    };
});

const heading = props.isEdit ? 'Modifier Candidat' : 'Nouveau Candidat';
</script>

<template>

    <div>
        <FileLibrary />
        <form class="form-page" @submit.prevent="submit">
            <FileLibrary @submit="form.media = $event" />
            <article class="left-form">
                <Back :title="heading" :back="route(routes.users.students.index)" />
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
                            <Select
                                v-model="form.ville"
                                label="Ville"
                                :show-search="false"
                                :items="[
                                    { id: 'Creil', name: 'Creil' },
                                    { id: 'Toulouse', name: 'Toulouse' },
                                ]"
                                :error="form.errors.ville"
                                class="flex-1"
                                placeholder="Ville"
                                required
                            />
                            <Select
                                id="postal"
                                v-model="form.postal"
                                :error="form.errors.postal"
                                label="Code postal"
                                :show-search="false"
                                :items="[
                                    { id: '60100', name: '60100 - Creil' },
                                    { id: '31300', name: '31300 - Toulouse' },
                                ]"
                                class="flex-1"
                                placeholder="Code postal"
                                required
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
                                    <Button link info :icon="AdjustIcon" @click="selectedBalance = true"> Modifier </Button>
                                </dt>
                                <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">
                                    {{ currentBalance }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm/6 font-medium text-gray-900">Estimation</dt>
                                <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">
                                    {{ data.student?.review_monitor?.estimation || 0 }}
                                </dd>
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
                    <div class="flex items-center justify-between">
                        <label class="block font-medium text-xs text-gray-600 mb-0.5 mt-1">Cpf </label>
                        <Switch v-model="form.is_cpf" class="text-xs bg-gray-200" size="sm" />
                    </div>
                            <div class="flex items-center justify-between">
                        <label class="block font-medium text-xs text-gray-600 mb-0.5 mt-1">Type de boite </label>
                        <TabSwitch
                            v-model="form.boite_type"
                            size="xs"
                            :items="[
                                { id: '0', name: 'Manuel' },
                                { id: '1', name: 'Auto' },
                            ]"
                        />
                    </div>

                    <!-- <Card v-if="isEdit" block>
                        <EmptyState
                            :title="studentContract ? 'Contract disponible' : 'Aucun contract'"
                            :class="['pb-4 pt-2', studentContract ? 'text-green-500' : '']"
                            :image="NoteIcon"
                        >
                            <p class="font-bold">
                                {{
                                    studentContract
                                        ? 'Voir le contract de premiere séance'
                                        : "Vous n'avez pas encore le contrat pour cet eleve"
                                }}.
                            </p>
                            <Button v-if="studentContract" class="mt-4" variant="success"> Telecharger le contrat </Button>
                        </EmptyState>
                    </Card> -->
                </div>
                <div style="margin-top: 10px">
                    <ButtonGroup vertical class="form-actions" :actions="actions" />
                </div>

                <AddBalanceDrawer :show="selectedBalance" :user="data" @close="selectedBalance = false" @new-balance="setBalance" />
            </article>
        </form>
    </div>
</template>
