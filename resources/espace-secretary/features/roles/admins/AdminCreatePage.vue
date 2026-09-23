<script setup lang="ts">
import { Page } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { AdminForm } from './partials';

const uuidPreview = Math.random().toString(16).slice(2);
</script>

<template>
    <Page width="full" padding="none">
        <AdminForm :uuid="uuidPreview" />
    </Page>
</template>
