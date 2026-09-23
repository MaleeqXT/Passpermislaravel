<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Logo, InputField, Button } from '@shared/components';

const form = useForm({
    password: '',
});

const passwordInput = ref(null);

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();

            passwordInput.value.focus();
        },
    });
};
</script>

<template>
    <Head title="Secure Area" />

    <Logo>
        <template #logo>
            <Logo />
        </template>

        <div class="mb-4 text-sm text-gray-600">
            This is a secure area of the application. Please confirm your password before continuing.
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputField
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    label="password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                    :error="form.errors.password"
                />
            </div>

            <div class="flex justify-end mt-4">
                <Button class="ml-4" :loading="form.processing" :disabled="!form.isDirty"> Confirm </Button>
            </div>
        </form>
    </Logo>
</template>
