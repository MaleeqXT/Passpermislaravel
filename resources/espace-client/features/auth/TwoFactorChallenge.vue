<script setup lang="ts">
import { nextTick, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Logo, Card, Button, InputField } from '@shared/components';

const recovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const recoveryCodeInput = ref(null);
const codeInput = ref(null);

const toggleRecovery = async () => {
    recovery.value ^= true;

    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value.focus();
        form.code = '';
    } else {
        codeInput.value.focus();
        form.recovery_code = '';
    }
};

const submit = () => {
    form.post(route('two-factor.login'));
};
</script>

<template>
    <Head title="Two-factor Confirmation" />

    <Logo />
    <Card block>
        <div class="mb-4 text-sm text-gray-600">
            <template v-if="!recovery">
                Please confirm access to your account by entering the authentication code provided by your authenticator application.
            </template>

            <template v-else> Please confirm access to your account by entering one of your emergency recovery codes. </template>
        </div>

        <form @submit.prevent="submit">
            <InputField
                v-if="!recovery"
                id="code"
                ref="codeInput"
                v-model="form.code"
                label="code"
                type="text"
                inputmode="numeric"
                class="mt-1 block w-full"
                autofocus
                autocomplete="one-time-code"
                :errors="form.errors.code"
            />

            <InputField
                v-else
                id="recovery_code"
                ref="recoveryCodeInput"
                v-model="form.recovery_code"
                label="Recovery Code"
                type="text"
                class="mt-1 block w-full"
                autocomplete="one-time-code"
                :errors="form.errors.recovery_code"
            />

            <div class="flex items-center justify-end mt-4">
                <button
                    type="button"
                    class="text-sm text-gray-600 hover:text-gray-900 underline cursor-pointer"
                    @click.prevent="toggleRecovery"
                >
                    <template v-if="!recovery"> Use a recovery code </template>

                    <template v-else> Use an authentication code </template>
                </button>

                <Button class="ml-4" :loading="form.processing" :disabled="form.isDirty"> Log in </Button>
            </div>
        </form>
    </Card>
</template>
