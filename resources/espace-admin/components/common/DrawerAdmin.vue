<template>
    <SideDrawer title="Menu" :show="drawer.show()" @close="drawer.close()">
        <dl class="p-4 border-b">
            <dd class="flex items-center gap-3">
                <Thumb :src="getFilePath(user as UserType)" :size="SizeEnum.MD" />
                <div class="flex-1">
                    <b v-if="user.name">{{ user.name }}</b>
                    <p v-if="user.email">{{ user.email }}</p>
                </div>
            </dd>
        </dl>
        <div v-for="(group, key) in drawerNavigation" :key="key" class="flex flex-col px-4 pt-2 text-md">
            <span class="font-semibold text-sm pt-2 pb-1 block text-gray-600 capitalize">
                {{ group.name }}
            </span>
            <ul class="bg-white rounded-lg shadow-sm font-medium divide-y overflow-clip" @click="drawer.close()">
                <Link
                    v-for="(item, idx) in group.children"
                    :key="idx"
                    as="li"
                    :href="item.href || '#'"
                    class="group flex items-center gap-x-3 px-3 py-2 btn-m hover:bg-gray-200"
                >
                    <span class="flex-1">{{ item.name }}</span>
                    <ChevronRightIcon class="w-5" />
                </Link>
            </ul>
        </div>
        <span class="flex-grow"></span>
        <div class="p-3 sticky bottom-16 h-fit">
            <Button variant="danger" full @click="logout()"> Se déconnecter </Button>
        </div>
    </SideDrawer>
</template>

<script setup lang="ts">
import { Button, SideDrawer, Thumb } from '@shared/components';
import { useApp } from '@shared/stores';
import { getFilePath } from '@shared/utils';
import { ChevronRightIcon } from '@adersolutions/icons';
import { Link } from '@inertiajs/vue3';
import { drawerNavigation } from '@espace-admin/enums';
import { SizeEnum } from '@shared/enums';
import type { UserType } from '@common/types';
const { user, drawer, logout } = useApp();
</script>
