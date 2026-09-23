<script setup lang="ts">
import { StudentForm } from '../partials';
import ViewContainer from './ViewContainer.vue';

defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    studentContract: Object,
});
</script>

<template>
    <ViewContainer :user="user">
        <StudentForm :key="user.updated_at" :data="user" is-edit :studentContract="studentContract" />
    </ViewContainer>
</template>
