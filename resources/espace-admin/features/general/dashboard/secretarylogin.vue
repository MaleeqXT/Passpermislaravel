<script setup lang="ts">
import { Page } from '@shared/components';

defineProps<{ ahmar: string }>();
</script>
<template>
    <Page padding="none" width="full" class="pb-20">
        <h1>{{ ahmar }}</h1>
    </Page>
</template>
