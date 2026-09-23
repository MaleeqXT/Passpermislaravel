<script setup lang="ts">
import { CpfItem } from './partials';
import ViewContainer from './ViewContainer.vue';

defineProps({
    student: {
        type: Object,
        default: () => ({}),
    },
    cpfs: {
        type: Object,
        default: () => ({}),
    },
});
</script>
<template>
    <ViewContainer :user="student">
        <div class="p-5">
            <CpfItem v-for="cpf in cpfs.data" :key="cpf.id" :cpf="cpf" />
        </div>
    </ViewContainer>
</template>
