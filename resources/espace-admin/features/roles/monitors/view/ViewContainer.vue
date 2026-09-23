<script setup lang="ts">
import { onUnmounted } from 'vue';
import { Button, Card, Page, SecondSidebar, SideDrawer } from '@shared/components';
import { pagesNavigation } from './monitor';
import { useApp } from '@shared/stores';
import { routes } from '@espace-admin/routes';
import { ProfileSection } from '@espace-admin/components';
import type { MonitorType } from '@common/types';
import type { ButtonType } from '@shared/types';
import RapportHours from '../partials/RapportHours.txt';

type PropsType = {
    monitor: MonitorType;
};

const props = defineProps<PropsType>();

const { drawer } = useApp();
const actions: ButtonType[] = [
    // {
    //     label: 'Rapport des heures',
    //     variant: 'dark',
    //     full: true,
    //     onAction: () => drawer.open('rapport'),
    // },
    {
        label: 'Connecter',
        variant: 'warning',
        self: true,
        full: true,
        href: route(routes.impersonate.start, props.monitor.user_id || '-'),
    },
];
onUnmounted(() => drawer.close());
</script>
<template>
    <Page width="full" padding="none">
        <template #sidebar>
            <!--  -->
            <SecondSidebar :nav-items="pagesNavigation(monitor.id)">
                <template #top>
                    <ProfileSection :user="monitor.user" />
                </template>
                <li class="mt-auto sticky bottom-0 !border-t-0 p-4">
                    <Card block padding="sm" class="!mb-0">
                        <Button v-for="action in actions" :key="action.label" v-bind="action" @click="action.onAction?.()">
                            {{ action.label }}
                        </Button>
                    </Card>
                </li>
            </SecondSidebar>
        </template>

        <div class="w-full">
            <slot />
        </div>
        <!-- <SideDrawer title="Rapport des heures" :show="drawer.show('rapport')" @close="drawer.close()">
        </SideDrawer> -->
    </Page>
</template>
