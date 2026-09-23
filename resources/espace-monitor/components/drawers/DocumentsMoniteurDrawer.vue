<template>
    <Drawer :show="drawer.show('car-documents-menu')" z-index="z-[902]" title="Documents personnels" back="Retour" @close="onClose">
        <ul class="bg-white rounded-lg shadow-sm font-medium divide-y overflow-clip mx-3">
            <Link
                v-for="(item, key) in docsNav"
                :key="key"
                as="li"
                :href="item.href || '#'"
                class="group flex items-center gap-x-3 px-3 py-2 btn-m hover:bg-gray-200"
                @click="drawer.close"
            >
                <span class="flex-1">{{ item.name }}</span>
                <ChevronRightIcon class="w-4" />
            </Link>
        </ul>
    </Drawer>
</template>

<script setup lang="ts">
import { Drawer } from '@shared/components';
import { useApp } from '@shared/stores';
import { ChevronRightIcon } from '@adersolutions/icons';
import { Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { routes } from '@espace-monitor/routes';

const docsNav = [
    {
        name: "Pièce d'identité",
        href: route(routes.settings.documentsPersonel.identities),
    },
    {
        name: 'Permis de Conduire',
        href: route(routes.settings.documentsPersonel.permis),
    },
    {
        name: "Diplôme d'enseignement",
        href: route(routes.settings.documentsPersonel.diplom),
    },
];
const { drawer } = useApp();

const onClose = () => {
    drawer.open('cars-menu');
};
</script>
