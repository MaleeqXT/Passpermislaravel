<script setup lang="ts">
import { onUnmounted } from 'vue';
import { Button, Card, Page, SecondSidebar, SideDrawer } from '@shared/components';
import { RapportHours } from './partials';
import { pagesNavigation } from './StudentsView';
import { useApp } from '@shared/stores';
import { routes } from '@espace-secretary/routes';
import { ProfileSection } from '../../../../components/common';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
});

const { drawer } = useApp();
const actions = [
    {
        label: 'Rapport des heures',
        variant: 'dark',
        full: true,
        onAction: () => drawer.open('rapport'),
    },
    {
        label: 'Connecter',
        variant: 'warning',
        self: true,
        full: true,
        href: route(routes.impersonate.start, props.user.id || '-'),
    },
];
onUnmounted(() => drawer.close());
</script>
<template>
    <Page width="full" padding="none">
        <template #sidebar>
            <SecondSidebar :nav-items="pagesNavigation(user.student?.id || '-')">
                <template #top>
                    <ProfileSection :user="user" />
                </template>
                <li class="mt-auto sticky bottom-0 !border-t-0 p-4">
                    <Card block padding="sm" class="!mb-0">
                        <Button v-for="action in actions" :key="action.label" v-bind="action" @click="action.onAction?.()">
                            {{ action.label }}
                        </Button>
                        <!-- {{ user?.name || 'CPF DOCUMENTS' }} -->
                    </Card>
                </li>
            </SecondSidebar>
        </template>

        <div class="w-full">
            <slot />
        </div>
        <SideDrawer title="Rapport des heures" :show="drawer.show('rapport')" @close="drawer.close()">
            <RapportHours :sid="user.student?.id || user.id" />
        </SideDrawer>
    </Page>
</template>
