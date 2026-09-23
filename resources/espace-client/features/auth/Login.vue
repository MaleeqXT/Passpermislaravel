<script setup lang="ts">
import { routes } from '@espace-client/routes';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { InputField, Button, Logo, CheckField } from '@shared/components';
import { useMutation } from '@shared/hooks';
import { getParams } from '@shared/utils';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

// interface FormData {
//     email: string;
//     password: string;
//     remember: string | boolean;
// }

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: data.remember ? 'on' : '',
        redirect_to: getParams().redirect_to || undefined,
    }));
    form.post(route('login'), {
        // onSuccess: () => {},
        onError: () => {
            form.password = '';
        },
    });
    // .then(() => {
    //     // location.href = '/admin';
    // })
    // .catch(() => {
    //     form.password = '';
    // });
};
</script>

<template>
    <Head title="Connectez-vous à votre compte" />
    <main class="h-screen flex-center flex-col w-full bg-gradient-to-b from-primary to-dark px-2">
        <div class="fixed z-0 inset-0 opacity-70 pointer-events-none">
            <img src="/assets/bg.svg" class="object-cover w-full h-full" />
        </div>
        <div class="sm:mx-auto sm:w-full sm:max-w-md mb-10 md:hidden -mt-32">
            <!-- <h1 class="mt-6 text-center text-2xl font-bold tracking-tight text-white mb-4">
                Bienvenue à <span>Easy <span class="text-dark">monitor</span></span>
            </h1> -->
            <p class="gap-2 flex-center text-xl font-bold text-white">
                <Logo class="w-fit" black />
            </p>
        </div>
        <section class="sm:max-w-[900px] w-full mx-auto bg-white flex max-md:flex-col p-5 md:p-3 shadow-box rounded-xl gap-4 relative">
            <article class="flex-1">
                <img src="/assets/login-compte.png" class="rounded-xl max-md:h-[90px] w-full object-cover object-bottom" />
            </article>
            <article class="flex-1 md:px-10">
                <div class="sm:mx-auto sm:w-full sm:max-w-md my-10 hidden md:block">
                    <div class="flex-center flex-col text-lg font-semibold">
                        <Logo class="h-10 inline" />
                        <!-- <span>Easy <span class="text-primary">monitor</span></span> -->
                    </div>
                    <h1 class="mt-16 text-center text-2xl font-bold tracking-tight text-gray-900">Bonjour</h1>
                    <h2 class="mt-0 text-center text-lg font-medium tracking-tight text-gray-900">Connectez-vous à votre compte</h2>
                </div>
                <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                    {{ status }}
                </div>
                <form class="space-y-5" @submit.prevent="submit">
                    <InputField
                        v-model="form.email"
                        label="Adresse e-mail"
                        :error="form.errors.email"
                        type="email"
                        anme="email"
                        autocomplete="email"
                        class="mt-1 block w-full"
                    />
                    <InputField
                        id="password"
                        v-model="form.password"
                        label="Mot de passe"
                        :error="form.errors.password"
                        type="password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                    />

                    <CheckField v-model:checked="form.remember" label="Rappelez-vous de moi" />
                    <div class="flex flex-col">
                        <Button submit variant="primary" full :loading="form.processing"> Se connecter </Button>

                        <Link v-if="canResetPassword" :href="route('password.request')" class="btn btn-link mt-5 mb-2 !px-0">
                            Mot de passe oublié?
                        </Link>
                        <p>
                            <span class="text-sm">Vous n'avez pas de compte?</span>
                            <Link :href="route(routes.register.student.create)" class="btn btn-link mb-2"> S'inscrire maintenant</Link>.
                        </p>
                    </div>
                </form>
            </article>
        </section>
    </main>
</template>
