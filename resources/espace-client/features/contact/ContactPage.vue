<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { InputField } from '@shared/components';
import { ClientButton, PageContainer } from '@espace-client/components';
import { routes } from '@espace-client/routes';
import { LocationIcon } from '@adersolutions/icons';

const form = useForm({
    prenom: '',
    nom: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
});
const onSendMail = () => {
    form.post(route(routes.contact.send), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <PageContainer class="min-h-fit">
        <div class="relative isolate">
            <div class="mx-auto grid max-w-7xl grid-cols-1 lg:grid-cols-2 divide-x">
                <div class="relative px-6 pb-20 pt-16 md:pt-32 lg:static lg:px-8 lg:py-48">
                    <div class="mx-auto max-w-xl lg:mx-0 lg:max-w-lg">
                        <div class="absolute inset-y-0 left-0 -z-10 w-full overflow-hidden bg-pattern opacity-50 lg:w-1/2"></div>
                        <img src="/assets/clients/contact2.webp" alt="" class="w-60 mb-6" />
                        <h2 class="text-pretty text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
                            Besoin de nous contacter
                        </h2>
                        <p class="mt-6 text-lg/8 text-gray-600">Nous vous recontacterons dans les 24 H.</p>
                        <ul class="mt-10 space-y-4 text-base/7 text-gray-600">
                            <li class="font-bold text-dark2 font-lg">Nos Agences et points de RDV</li>
                            <li class="flex items-center gap-x-2">
                                <LocationIcon class="h-6 w-6 text-gray-400" />

                                139 Bd Déodat de Sévérac, 31300 Toulouse

                            </li>
                             <li class="flex items-center gap-x-2">
                                <LocationIcon class="h-6 w-6 text-gray-400" />

Boulevard André Netwiller, 31200 Toulouse
                            </li>
                            <li class="flex items-center gap-x-2">
                                <LocationIcon class="h-6 w-6 text-gray-400" />
                                15 rue des Pierres 60100 Creil
                            </li>

                            <!-- <li class="flex items-center gap-x-2">
                                <LocationIcon class="h-6 w-6 text-gray-400" />

                                Saint-Denis-Université (Terminus Métro Ligne 13)
                            </li> -->
                        </ul>
                    </div>
                </div>
                <div class="bg-white absolute w-1/2 inset-y-0 right-0 top-0 max-md:hidden"></div>
                <form @submit.prevent="onSendMail" class="px-6 pb-24 pt-20 sm:pb-32 lg:px-8 lg:py-48 max-md:bg-white">
                    <div class="mx-auto max-w-xl lg:mr-0 lg:max-w-lg">
                        <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
                            <InputField id="first-name" v-model="form.prenom" label="Prénom*" :error="form.errors.prenom" />
                            <InputField id="last-name" v-model="form.nom" label="Nom*" :error="form.errors.nom" />

                            <div class="sm:col-span-2">
                                <InputField id="email" v-model="form.email" label="Email*" :error="form.errors.email" />
                            </div>
                            <div class="sm:col-span-2">
                                <InputField id="phone" v-model="form.phone" label="Télèphone " :error="form.errors.phone" />
                            </div>
                            <div class="sm:col-span-2">
                                <InputField id="subject" v-model="form.subject" label="Sujet*" :error="form.errors.subject" />
                            </div>
                            <div class="sm:col-span-2">
                                <InputField
                                    id="message"
                                    v-model="form.message"
                                    label="Décrivez votre besoin en formation*"
                                    :error="form.errors.message"
                                    :multiline="6"
                                    placeholder="si vous êtes en situation de handicap"
                                />
                            </div>
                        </div>
                        <div class="mt-8 flex justify-end">
                            <ClientButton type="submit"> Envoyer le message </ClientButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </PageContainer>
</template>
