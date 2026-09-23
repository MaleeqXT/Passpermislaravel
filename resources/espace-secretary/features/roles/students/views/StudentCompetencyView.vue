<script setup lang="ts">
import { routes } from '@espace-admin/routes';
import { Competency, CompetenciesGroup } from './partials';
import { useQuery } from '@shared/hooks';
import ViewContainer from './ViewContainer.vue';
import { Alerts, Back } from '@shared/components';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
});

const competenciesQuery = useQuery(
    {
        url: route(routes.api.competences.index, props.user?.student.id),
    },
    true
);
</script>

<template>
    <ViewContainer :user="user">
        <div class="max-w-screen-lg w-full mx-auto px-5">
            <Back title="Competences" :back="route(routes.users.students.index)" />

            <Alerts />
            <CompetenciesGroup v-if="competenciesQuery.data?.length" v-slot="{ item }" :items="competenciesQuery.data">
                <Competency v-if="item" :group="item" />
            </CompetenciesGroup>
        </div>
    </ViewContainer>
</template>
