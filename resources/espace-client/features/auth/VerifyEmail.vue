<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Logo, Card, Button } from '@shared/components';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Vérification de l'adresse email" />

    <Logo />
    <Card block padding>
        <div class="mb-4 text-sm text-gray-600">
            Avant de continuer, pourriez-vous vérifier votre adresse email en cliquant sur le lien que nous venons de vous envoyer ? Si vous
            n'avez pas reçu l'email, nous vous enverrons volontiers un autre.
        </div>

        <div v-if="verificationLinkSent" class="mb-4 font-medium text-sm text-green-600">
            Un nouveau lien de vérification a été envoyé à l'adresse email que vous avez fournie dans les paramètres de votre profil.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <Button :loading="form.processing" :disabled="form.isDirty"> Renvoyer l'email de vérification </Button>

                <div>
                    <Link
                        :href="route('profile.show')"
                        class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Modifier le profil
                    </Link>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ml-2"
                    >
                        Se déconnecter
                    </Link>
                </div>
            </div>
        </form>
    </Card>
</template>
