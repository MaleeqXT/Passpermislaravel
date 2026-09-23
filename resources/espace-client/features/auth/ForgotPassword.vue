<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Logo, Card, Button, InputField } from '@shared/components';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />

    <main class="h-screen flex-center flex-col">
        <Logo class="mb-10" />
        <Card padding block class="max-w-md mx-auto w-full">
            <div class="text-sm text-gray-600">
                Vous avez oublié votre mot de passe ? Aucun problème. Communiquez-nous simplement votre adresse e-mail et nous vous
                enverrons par e-mail un lien de réinitialisation de mot de passe qui vous permettra d'en choisir un nouveau.
            </div>

            <div v-if="status" class="font-medium text-sm text-green-600">
                {{ status }}
            </div>

            <form @submit.prevent="submit">
                <div>
                    <InputField
                        id="email"
                        v-model="form.email"
                        label="Email"
                        type="email"
                        class="mt-1 block w-full"
                        required
                        autofocus
                        autocomplete="username"
                        :error="form.errors.email"
                    />
                </div>

                <Button
                    variant="primary"
                    full
                    type="submit"
                    class="mt-6"
                    :loading="form.processing"
                    :disabled="form.processing || !form.isDirty"
                >
                    Lien de réinitialisation du mot de passe par e-mail
                </Button>
            </form>
        </Card>
    </main>
</template>
