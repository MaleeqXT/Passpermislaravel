<script setup lang="ts">
import type { CompetencyGroupType } from '@common/types';
import CompetenceItem from '@espace-student/features/competences/partials/CompetenceItem.vue';
type PropsType = {
    group: CompetencyGroupType;
};

defineProps<PropsType>();
</script>
<template>
    <ul class="flex flex-col divide-y divide-gray-200 bg-white box mt-5">
        <CompetenceItem v-for="item in group.competencies" :key="item.id" :item="item" class="px-3" />
    </ul>
</template>
