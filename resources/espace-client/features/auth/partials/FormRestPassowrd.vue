<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Logo, Card, Button, InputField } from '@shared/components';

const props = defineProps({
    email: Object,
    token: String,
});
const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
<template>
    <form class="h-screen flex-col flex-center" @submit.prevent="submit">
        <Logo class="mb-10" />
        <Card padding block class="max-w-md mx-auto w-full">
            <InputField
                id="email"
                v-model="form.email"
                label="E-mail"
                type="email"
                class="bg-slate-200 p-2 rounded-md"
                required
                disabled
                :error="form.errors.email"
            />

            <InputField
                id="password"
                v-model="form.password"
                label="Mot de passe"
                type="password"
                class="mt-1 block w-full"
                required
                autocomplete="new-password"
                :error="form.errors.password"
            />
            <InputField
                id="password_confirmation"
                v-model="form.password_confirmation"
                label="Confirmer le mot de passe"
                type="password"
                class="mt-1 block w-full"
                required
                autocomplete="new-password"
                :error="form.errors.password_confirmation"
            />
            <Button variant="primary" full submit :loading="form.processing" :disabled="form.processing || !form.isDirty" class="mt-6">
                Difinir le mot de passe
            </Button>
        </Card>
    </form>
</template>
