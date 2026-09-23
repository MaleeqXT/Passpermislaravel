<script setup lang="ts">
import { Alerts, Back, Card } from '@shared/components';
import { CommentsSection, InfosSection } from './partials';
import ViewContainer from './ViewContainer.vue';
import type { UserType } from '@common/types';
import { routes } from '@espace-admin/routes';

type PropsType = {
    user: UserType;
};

defineProps<PropsType>();
</script>
<template>
    <ViewContainer :user="user">
        <div class="max-w-screen-md w-full mx-auto px-5">
            <Back title="Informations de condidat" :back="route(routes.users.students.index)" />
            <Alerts />
            <InfosSection :user="user" />
            <CommentsSection :student="user?.student" />
        </div>
    </ViewContainer>
</template>
