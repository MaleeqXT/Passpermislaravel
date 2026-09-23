<template>
    <Drawer :show="drawer.show()" title="Profile" @close="drawer.close()">
        <StudentProgressStatsCard class="px-4" @on-action="drawer.close()" />
        <div v-for="(group, key) in navs" :key="key" class="flex flex-col px-4 pt-2 text-md">
            <span class="font-semibold text-sm pt-2 pb-1 block text-gray-600 capitalize">
                {{ group.name }}
            </span>
            <div class="bg-white rounded-lg shadow-sm font-medium divide-y overflow-clip" @click="drawer.close()">
                <component
                    v-for="(item, idx) in group.children"
                    :is="item.external ? 'a' : Link"
                    :target="item.external ? '_blank' : '_self'"
                    :key="idx"
                    :href="item.href || '#'"
                    class="group flex items-center gap-x-3 px-3 py-2 btn-m hover:bg-gray-200"
                >
                    <span class="flex-1">{{ item.name }}</span>
                    <ChevronRightIcon class="w-5" />
                </component>
            </div>
        </div>
        <span class="flex-1"></span>
        <div class="p-2">
            <Button variant="danger" full @click.stop="logout()"> Se déconnecter </Button>
        </div>
    </Drawer>
</template>

<script setup lang="ts">
import { Button, Drawer } from '@shared/components';
import { useApp } from '@shared/stores';
import { ChevronRightIcon } from '@adersolutions/icons';
import { Link } from '@inertiajs/vue3';
import { drawerNavigation } from '@espace-student/constants';
import StudentProgressStatsCard from './StudentProgressStatsCard.vue';
import { NavigationItemType } from '@shared/types';
import { routes } from '@espace-student/routes';
import { computed } from 'vue';

const { drawer, logout, docs, user, isLogged } = useApp();

const canSeeOffers = computed(() => {
    // Show to SuperAdmin or admin@pf.com
    if (user && (user.name === 'SuperAdmin' || user.email === 'admin@pf.com')) {
        return true;
    }

    // Show to Creil/Toulouse students only
    if (user) {
        const ville = String(user.ville || '').toLowerCase().trim();
        const postal = String(user.postal || '').trim();
        const isCreil = ville.includes('creil') || postal === '60100';
        const isToulouse = ville.includes('toulouse') || postal === '31300';
        return isCreil || isToulouse;
    }

    return false;
});

const filteredDrawerNavigation = computed(() => {
    return drawerNavigation.map(group => ({
        ...group,
        children: group.children.filter(item => {
            // Filter out 'Offres' if user can't see offers
            if (item.name === 'Offres' && !canSeeOffers.value) {
                return false;
            }
            return true;
        }),
    }));
});

const firstRevNav: NavigationItemType[] =
    docs?.fr && docs?.eva
        ? [
              {
                  name: 'Formation',
                  children: [
                      {
                          name: 'Contrat de Formation',
                          href: route(routes.pdf.contract, docs.fr.id),
                          external: true,
                      },
                      {
                          name: "Fiche d'évaluation",
                          href: route(routes.pdf.evaluations, docs.eva.id),
                          external: true,
                      },
                  ],
              },
          ]
        : [];

const navs = computed(() => [...filteredDrawerNavigation.value, ...firstRevNav]);
</script>
