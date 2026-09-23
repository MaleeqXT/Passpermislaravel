<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FormRestPassowrd } from './partials';
defineProps({
    email: Object,
    token: String,
});
</script>
<template>
    <Head title="Difinir le mot de passe" />
    <FormRestPassowrd :email="email" :token="token" />
</template>
