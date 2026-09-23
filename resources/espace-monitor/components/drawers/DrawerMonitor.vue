<template>
    <Drawer :show="drawer.show()" title="Parametres" @close="drawer.close()">
        <dl class="px-3">
            <Link
                as="dd"
                :href="route(routes.settings.profile.index)"
                class="flex items-center gap-3 bg-rainbow rainbow-opacity-30 bg-dark text-white rounded-lg box p-2"
                @click.prevent="drawer.close()"
            >
                <Thumb :src="getFilePath(user)" :size="SizeEnum.MD" />
                <div class="flex-1">
                    <b v-if="user.name">{{ user.name }}</b>
                    <p v-if="user.email">{{ user.email }}</p>
                </div>
                <ChevronRightIcon class="w-5 mr-3" />
            </Link>
        </dl>
        <div v-for="(group, key) in drawerNavigation" :key="key" class="flex flex-col px-4 pt-2 text-md">
            <span class="font-semibold text-sm pt-2 pb-1 block text-gray-600 capitalize">
                {{ group.name }}
            </span>
            <ul class="bg-white rounded-lg shadow-sm font-medium divide-y overflow-clip">
                <component
                    :is="item.href ? Link : 'li'"
                    v-for="(item, idx) in group.children"
                    :key="idx"
                    as="li"
                    :href="item.href || '#'"
                    class="group flex items-center gap-x-3 px-3 py-2 btn-m hover:bg-gray-200"
                    @click.prevent="item.href ? drawer.close() : item.onAction?.()"
                >
                    <span class="flex-1">{{ item.name }}</span>
                    <ChevronRightIcon class="w-5" />
                </component>
            </ul>
        </div>
        <span class="flex-1"></span>
        <div class="p-2">
            <Button variant="danger" full @click.stop="logout()"> Se déconnecter </Button>
        </div>
    </Drawer>
</template>

<script setup lang="ts">
import { Button, Drawer, Thumb } from '@shared/components';
import { useApp } from '@shared/stores';
import { getFilePath } from '@shared/utils';
import { ChevronRightIcon } from '@adersolutions/icons';
import { Link } from '@inertiajs/vue3';
import { drawerNavigation } from '@espace-monitor/constants';
import { routes } from '@espace-monitor/routes';
import { SizeEnum } from '@shared/enums';

const { user, drawer, logout } = useApp();
</script>
