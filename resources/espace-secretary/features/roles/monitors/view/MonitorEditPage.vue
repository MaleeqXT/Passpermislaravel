<script setup lang="ts">
import { MonitorForm } from '../partials';
import ViewContainer from './ViewContainer.vue';
import type { MonitorType } from '@common/types';

type PropsType = {
    monitor: MonitorType;
};

defineProps<PropsType>();
</script>

<template>
    <ViewContainer :monitor="monitor">
        <MonitorForm :data="monitor" is-edit />
    </ViewContainer>
</template>
