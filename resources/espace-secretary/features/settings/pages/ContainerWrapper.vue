<script setup lang="ts">
import { onUnmounted } from 'vue';
import { Button, Card, Page, SecondSidebar, SideDrawer } from '@shared/components';
import { useApp } from '@shared/stores';
import { routes } from '@espace-secretary/routes';
import { ProfileSection } from '../../../components/common';
import { CatalogIcon, HomeFilledIcon } from '@adersolutions/icons';

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
});

const { drawer } = useApp();

const pagesNavigation = [
    {
        icon: HomeFilledIcon,
        path: route(routes.pages.home.index),
        pathname: '/home',
        title: 'Accueil',
    },
    {
        icon: CatalogIcon,
        path: route(routes.pages.code.index),
        pathname: '/code',
        title: 'Code',
    },
];
</script>
<template>
    <Page width="full" padding="none">
        <template #sidebar>
            <SecondSidebar title="Pages" :nav-items="pagesNavigation"> </SecondSidebar>
        </template>

        <div class="w-full">
            <slot />
        </div>
    </Page>
</template>
