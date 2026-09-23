<script setup lang="ts">
import { computed, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Card,
    Button,
    DateField,
    Select,
    FileLibrary,
    SingleImageField,
    TabSwitch,
    InputField,
    ButtonGroup,
    Back,
    Alerts,
} from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useAlert } from '@shared/stores';
import { GeneralStatus, LearningModeList } from '@common/enums';
import { SelectAreaPlaces } from '@common/components';
import { MinusIcon } from '@adersolutions/icons';
import { useFiles } from '@shared/hooks';
import type { MonitorType, ArrayListType } from '@common/types';
import type { GroupActionType } from '@shared/types';

type PropsType = {
    data?: MonitorType;
    isEdit?: boolean;
};
const props = withDefaults(defineProps<PropsType>(), {
    data: () => ({} as MonitorType),
    isEdit: false,
});
const alert = useAlert();
const places = reactive({
    selected: null,
    clear: 1,
});
const form = useForm({
    first_name: props.data.user?.first_name || '',
    last_name: props.data.user?.last_name || '',
    email: props.data.user?.email || '',
    status: props.data.user?.status || 1,
    adresse: props.data.user?.adresse || '',
    // experience: props.data.monitor?.details?.experience || 0,
    // dernier_experience: props.data.details?.dernier_experience || '',
    // details_experience: props.data.details?.details_experience || '',
    phone: props.data.user?.phone || '',
    media: props.data.user?.media || props.data.user?.profile_photo_url || null,
    sexe: props.data.user?.sexe || null,
    date_naissance: props.data.user?.date_naissance || null,
    postal: props.data.user?.postal || '',
    ville: props.data.user?.ville || '',
    role: 3,
    lieux: props.data.lieux || [],
    departement: props.data.details?.departement || '',
    numero_autorisation: props.data.details?.numero_autorisation,
    tarif_car: props.data.details?.tarif_car || null,
    tarif_enseignement: props.data.details?.tarif_enseignement,
    is_auto: props.data.details?.is_auto || false,
    // -----
    iban: props.data.account?.iban || '',
    bic: props.data.account?.bic || '',
    // -----
    password: '',
    password_confirmation: '',
    // status: props.account?.status || GeneralStatusEnum.ACTIVE,
});

const medias = useFiles();

const checkPassword = () => {
    if (form.password !== form.password_confirmation) {
        form.errors.password_confirmation = "le mot de passe n'est pas confirmé";
    } else {
        form.errors.password_confirmation = '';
    }
};
const onDeleteMedia = () => {
    if (props.isEdit && props.data.media?.id) {
        medias.delete(props.data.media.id).then(() => {
            form.media = null;
        });
    } else {
        form.media = null;
    }
};

const onAddPlace = () => {
    if (!places.selected) {
        return;
    }
    const item = form.lieux.find((item) => item.id === places.selected?.id);
    if (item) {
        alert.show({
            type: 'error',
            title: item.name + ' est deja selectioné',
        });
        return;
    }
    form.lieux.push(places.selected);
    places.clear += 1;
    places.selected = null;
};
const actions = computed((): GroupActionType[] => [
    {
        label: props.isEdit ? 'Enregister' : 'Nouveau Moniteur',
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
const heading = props.isEdit ? props.data.user?.name || '' : 'Nouveau Moniteur';

const submit = () => {
    form.transform((data) => {
        if (data.media?.id) {
            data.media = data.media.path;
        }
        data.lieux = data.lieux.map((item) => item.id);
        return data;
    });

    if (props.isEdit) {
        return form.put(route(routes.users.monitors.update, props.data?.id));
    }
    form.post(route(routes.users.monitors.store));
};
</script>
<template>
    <form class="form-page" @submit.prevent="submit">
        <FileLibrary @submit="form.media = $event" />
        <article class="left-form">
            <Back :title="heading" :back="route(routes.users.monitors.index)" />
            <div class="form-content">
                <Alerts />
                <Card block padding title="Informations personnel">
                    <section class="grid sm:grid-cols-2 gap-4">
                        <InputField id="firstName" v-model="form.first_name" :error="form.errors.first_name" label="Prenom" required />
                        <InputField id="lastName" v-model="form.last_name" :error="form.errors.last_name" label="Nom " required />
                        <InputField id="email" v-model="form.email" :error="form.errors.email" label="Email" required type="email" />
                        <InputField id="adress" v-model="form.adresse" :error="form.errors.adresse" label="Adress" required />
                        <InputField
                            id="tel"
                            v-model="form.phone"
                            :error="form.errors.phone"
                            label="Telephone"
                            required
                            type="tel"
                            mask="## ## ## ## ##"
                            :length="10"
                        />
                        <Select
                            v-model="form.sexe"
                            label="Genre"
                            :error="form.errors?.sexe"
                            :items="[
                                { id: 'Homme', name: 'Homme' },
                                { id: 'Femme', name: 'Femme' },
                            ]"
                            class="w-full"
                            :show-search="false"
                            required
                            placeholder="Moniteur genre"
                        />
                        <DateField v-model="form.date_naissance" :error="form.errors.date_naissance" birthday label="Date de naissence" />
                        <InputField id="ville" v-model="form.ville" :error="form.errors.ville" label="Ville" required />
                        <InputField
                            id="postal"
                            v-model="form.postal"
                            :error="form.errors.postal"
                            label="Code postal"
                            required
                            type="number"
                        />
                    </section>
                </Card>
                <Card block padding title="Bank informations" class="mt-7 flex flex-col px-3 flex-1">
                    <div class="flex flex-col gap-4">
                        <InputField
                            v-model="form.iban"
                            mask="AA ## XXXX ###### ########"
                            avoid-replace
                            :error="form.errors.iban"
                            label="IBAN"
                        />
                        <InputField v-model="form.bic" :length="11" :error="form.errors.bic" label="BIC" />
                    </div>
                </Card>
                <Card block padding title="Autres infos">
                    <div class="grid grid-cols-2 gap-4">
                        <InputField
                            id="departement"
                            v-model="form.departement"
                            :error="form.errors.departement"
                            label="Departement"
                            class="flex-1"
                        />
                        <InputField
                            id="numero_autorisation"
                            v-model="form.numero_autorisation"
                            :error="form.errors.numero_autorisation"
                            label="Numero d'autorisation"
                            mask="A ## ### #### #"
                            avoid-replace
                            input-class="appearance-none form-control"
                            class="flex-1"
                        />
                        <InputField
                            id="tarif_car"
                            v-model="form.tarif_car"
                            :error="form.errors.tarif_car"
                            label="Tarif car"
                            type="number"
                            input-class="appearance-none form-control"
                            suffix="€"
                            class="flex-1"
                        />
                        <InputField
                            id="tarif_enseignement"
                            v-model="form.tarif_enseignement"
                            :error="form.errors.tarif_enseignement"
                            label="Tarif enseignement"
                            type="number"
                            input-class="appearance-none form-control"
                            suffix="€"
                            class="flex-1"
                        />
                    </div>
                </Card>
                <Card block padding title="Zone & Lieux">
                    <ul class="flex flex-col overflow-y-auto max-h-80 divide-y bg-gray-50 shadow-sm rounded-xl">
                        <li v-for="(lieu, idx) in form.lieux" :key="lieu.id" class="flex gap-2 justify-between font-semibold p-1.5">
                            <span>{{ lieu.name }}</span>

                            <MinusIcon
                                class="hover:text-red-600 flex-center w-6 h-6 p-0.5 rounded-full hover:bg-red-100 btn-hover"
                                @click="form.lieux.splice(idx, 1)"
                            />
                        </li>
                    </ul>
                    <div class="flex gap-2 w-full items-center">
                        <SelectAreaPlaces :key="places.clear" class="contents" @change:full="places.selected = $event" />
                        <Button dark :disabled="!places.selected" class="!h-8" @click="onAddPlace">Ajoute</Button>
                    </div>
                </Card>
                <Card padding block title="Changer Mot de passe">
                    <div class="flex gap-4">
                        <InputField
                            id="password"
                            v-model="form.password"
                            :error="form.errors.password"
                            label="Mot de passe"
                            type="password"
                            class="flex-1"
                        />
                        <InputField
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :error="form.errors.password_confirmation"
                            label="Mot de passe confirmation"
                            type="password"
                            class="flex-1"
                            @blur="checkPassword"
                        />
                    </div>
                </Card>
            </div>

            <ButtonGroup vertical class="form-actions" :actions="actions" />
        </article>
        <article class="right-form">
            <div>
                <Select
                    v-model="form.status"
                    label="Status"
                    :items="Object.values(GeneralStatus)"
                    class="w-full"
                    :show-search="false"
                    placeholder="Moniteur status"
                />
               
                <SingleImageField
                    :error="form.errors.media"
                    :src="form.media?.id ? form.media?.path : form.media || null"
                    @delete="onDeleteMedia"
                />
            </div>
            <ButtonGroup vertical class="form-actions" :actions="actions" />
        </article>
    </form>
</template>
