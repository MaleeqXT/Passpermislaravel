<script setup lang="ts">
import { Page } from '@shared/components';
import { AdminForm } from './partials';

defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <Page width="full" padding="none">
        <AdminForm :data="user" is-edit />
    </Page>
</template>
