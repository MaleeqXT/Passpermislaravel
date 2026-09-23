<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Card, CheckField, InputField } from '@shared/components';
import { XCircleIcon } from '@adersolutions/icons';
import { useAlert, useAuth } from '@shared/stores';
import { routes } from '@espace-client/routes';
import Dialog from './Dialog.vue';
import axios from 'axios';
const alert = useAlert();
const authStore = useAuth();

const isProcessing = ref(false);
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = async () => {
    isProcessing.value = true;
    await axios
        .post(route(routes.api.auth.login), {
            email: form.email,
            password: form.password,
            remember: form.remember ? 'on' : '',
        })
        .then((res) => {
            if (res.data?.message === 'Success') {
                authStore.shouldReloadCartData = true;
                authStore.isActiveDialog = false;
                authStore.isAuthenticated = true;
                form.reset();
                alert.show({ type: 'success', title: 'Bienvenue' });
            }
        })
        .catch((e) => {
            form.errors.email = e.response.data.message;
            form.reset('password');
        });
    isProcessing.value = false;
};
</script>

<template>
    <Dialog :show="true" class-name="md:w-3/5 h-2/3 top-28 md:left-[20%]">
        <main class="relative lg:grid lg:grid-cols-2 bg-white h-full">
            <span class="absolute top-3 right-5 w-6 hover:text-red-600 text-dark cursor-pointer" @click="authStore.isActiveDialog = false">
                <XCircleIcon />
            </span>
            <section class="flex flex-col w-full items-center justify-center h-full px-8 lg:ml-10">
                <h1 class="font-semibold text-xl">Se Connecter</h1>
                <form class="py-3 flex flex-col gap-3 w-full items-start" @submit.prevent="submit">
                    <InputField v-model="form.email" class="w-full" label="Email" type="email" required :error="form.errors.email" />
                    <InputField
                        v-model="form.password"
                        class="w-full"
                        label="Password"
                        type="password"
                        required
                        :error="form.errors.password"
                    />
                    <div class="block mt-4 justify-start self-start">
                        <label class="flex items-center">
                            <CheckField v-model:checked="form.remember" name="remember" />
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
                    </div>
                    <Card class="flex gap-2 w-full items-center justify-center">
                        <input
                            type="submit"
                            class="btn btn-primary w-fit text-center mt-6 self-center px-16"
                            value="Se connecter"
                            :class="isProcessing && 'opacity-25'"
                            :disabled="!form.email || !form.password"
                        />
                        <input
                            class="btn btn-secondary w-40 cursor-pointer text-center mt-6 self-center"
                            value="Annuler"
                            @click="authStore.isActiveDialog = false"
                        />
                    </Card>
                </form>
            </section>
            <section class="self-center justify-end w-full hidden lg:flex">
                <img :src="`/assets/icons/auth/manCar.svg`" alt="auth image" class="w-10/12" />
            </section>
        </main>
    </Dialog>
</template>
