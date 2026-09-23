<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Card, Select, FileLibrary, SingleImageField, InputField, Alerts, Back, ButtonGroup } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { useApp } from '@shared/stores';
import { GeneralStatus } from '@common/enums';
import { useFiles } from '@shared/hooks';
import { GroupActionType } from '@shared/types';
const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
    actions: Array,
    isEdit: Boolean,
    uuid: String,
});
const form = useForm({
    first_name: props.data.first_name || '',
    last_name: props.data.last_name || '',
    email: props.data.email || '',
    status: props.data.status || 1,
    phone: props.data.phone || '',
    media: props.data?.media || props.data?.profile_photo_url || null,
    password: props.data.password || '',
    password_confirmation: props.data.password_confirmation || '',
});

const medias = useFiles();
const loading = ref(false);

const submit = () => {
    form.transform((data) => {
        if (data.media?.id) {
            data.media = data.media.path;
        }
        return data;
    });
    if (props.isEdit) {
        form.put(route(routes.users.admins.update, props.data.id));
    } else {
        form.post(route(routes.users.admins.store));
    }
    loading.value = true;
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

const actions = computed<GroupActionType[]>(() => [
    {
        label: `${props.isEdit ? 'Modifier' : 'Ajouter'} admin`,
        variant: 'primary',
        disabled: !form.isDirty,
        full: true,
        loading: form.processing,
        onAction: () => submit(),
    },
    {
        label: 'Rejeter',
        disabled: form.processing || !form.isDirty,
        variant: 'secondary',
        full: true,
        onAction: () => form.reset(),
    },
]);
const heading = props.isEdit ? props.data?.name || '' : 'Nouvel admin';
</script>
<template>
    <form class="form-page" @submit.prevent="submit">
        <FileLibrary @submit="form.media = $event" />
        <article class="left-form">
            <Back :title="heading" :back="route(routes.users.admins.index)" />
            <div class="form-content">
                <Alerts />
                <Card block padding title="Admin infos">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <InputField id="firstName" v-model="form.first_name" :error="form.errors.first_name" label="Prenom" required />
                        <InputField id="lastName" v-model="form.last_name" :error="form.errors.last_name" label="Nom " required />
                        <InputField id="email" v-model="form.email" :error="form.errors.email" label="Email" required type="email" />
                        <InputField id="tel" v-model="form.phone" :error="form.errors.phone" label="Telephone" required type="tel" />

                        <Select
                            v-model="form.status"
                            label="Status"
                            :items="Object.values(GeneralStatus)"
                            class="w-full"
                            placeholder="Admin status"
                        />
                    </div>
                </Card>
                <Card padding block title="Changer Mot de passe">
                    <div class="flex gap-4">
                        <InputField
                            id="password"
                            v-model="form.password"
                            :error="form.errors.password"
                            label="Mot de passe"
                            required
                            type="password"
                            class="flex-1"
                        />
                        <InputField
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :error="form.errors.password_confirmation"
                            label="Mot de passe confirmation"
                            required
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
                <SingleImageField :error="form.errors.media" :src="form.media?.path || form.media || null" @delete="onDeleteFile" />
            </div>
            <ButtonGroup vertical class="form-actions" :actions="actions" />
        </article>
    </form>
</template>
