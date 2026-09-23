<script setup lang="ts">
import { FormRestPassowrd } from '@espace-client/features/auth/partials';
import { Head } from '@inertiajs/vue3';
defineProps({
    email: Object,
    token: String,
});
</script>
<template>
    <Head title="Réinitialiser le mot de passe" />
    <FormRestPassowrd :email="email" :token="token" />
</template>
