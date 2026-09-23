<script setup lang="ts">
import { Page } from '@shared/components';
import { ExamForm } from './partials';
import type { ExamType } from '@common/types';
type PropsType = {
    exam: ExamType;
};
defineProps<PropsType>();
</script>

<template>
    <Page title="Liste d'examination" width="lg">
        <ExamForm is-edit :data="exam" />
    </Page>
</template>
