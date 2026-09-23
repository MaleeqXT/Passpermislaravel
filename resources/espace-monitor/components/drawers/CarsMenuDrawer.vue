<template>
    <Drawer :show="drawer.show('cars-menu')" z-index="z-[901]" title="Véhicules et Documents" back="Retour" @close="onClose">
        <ul class="bg-white rounded-lg shadow-sm font-medium divide-y overflow-clip mx-3">
            <component
                :is="item.href ? Link : 'li'"
                v-for="(item, key) in carNav"
                :key="key"
                as="li"
                :href="item.href || '#'"
                class="group flex items-center gap-x-3 px-3 py-2 btn-m hover:bg-gray-200"
                @click.prevent="item.href ? drawer.close() : item.onAction?.()"
            >
                <span class="flex-1">{{ item.name }}</span>
                <ChevronRightIcon class="w-4" />
            </component>
        </ul>
    </Drawer>
</template>

<script setup lang="ts">
import { Drawer } from '@shared/components';
import { useApp } from '@shared/stores';
import { ChevronRightIcon } from '@adersolutions/icons';
import { Link } from '@inertiajs/vue3';
import { routes } from '@espace-monitor/routes';
const { drawer } = useApp();

const carNav: any = [
    {
        name: 'Véhicules',
        href: route(routes.settings.cars.index),
    },
    // {
    //     name: 'Documents personnels',
    //     // href: route(routes.settings.documentsPersonel.index),
    //     onAction: () => drawer.open('car-documents-menu'),
    //     // drawer: 'documentsPersonel',
    // },

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
    {
        name: 'Documents professionnels',
        href: route(routes.settings.documentsProfessionnel.index),
    },
];

// const close = () => drawer.close();
const onClose = () => {
    drawer.open('value');
};

// onMounted(() => {
//     drawer.documentsPersonel = false;
// });
</script>
