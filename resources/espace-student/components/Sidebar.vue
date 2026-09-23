<template>
    <div>
        <div class="flex grow flex-col gap-y-3 overflow-y-auto border-r border-gray-300/70 h-full">
            <div class="flex-center py-0.5">
                <img class="h-16 object-contain px-4" src="/assets/logo.svg" alt="Logo" />
            </div>
            <nav class="flex flex-1 flex-col px-4">
                <ul :key="$page.component" role="list" class="flex flex-1 flex-col gap-1">
                    <template v-for="item in navigation(count.data)" :key="item.name">
                        <li v-if="item.grow" class="flex-1"></li>
                        <li v-else class="">
                            <Link
                                :href="item.href"
                                :class="[
                                    'group flex items-center gap-x-2 rounded-full h-12 px-3 text-lg leading-6 font-semibold cursor-pointer t-200 relative',
                                    isActive(item.href)
                                        ? 'bg-gradient-to-tr to-orange-100 from-primary text-white '
                                        : 'text-gray-700 hover:text-primary',
                                ]"
                            >
                                <component
                                    :is="item.icon"
                                    :class="[
                                        isActive(item.href) ? 'text-white' : 'text-slate-600 group-hover:text-primary',
                                        'h-6 w-6 shrink-0 ',
                                    ]"
                                    aria-hidden="true"
                                />

                                <span class="flex-1 leading-none mt-0.5">{{ item.name }}</span>
                                <b v-if="item.badge" class="shadow bg-green-500 w-5 h-5 flex-center rounded-full text-white">{{
                                    item.badge
                                }}</b>
                                <ChevronDownIcon v-if="item.children?.length" :class="['w-4 t-200']" />
                            </Link>
                        </li>
                    </template>
                </ul>
                <ItemImage
                    :src="getFilePath(user)"
                    size="w-10 h-10"
                    class="w-full p-1 mb-3 rounded-full t-200 btn-hover hover:bg-gradient-to-tr from-primary to-orange-400 hover:text-white"
                    :title="user.name"
                    @click="drawer.open()"
                />
            </nav>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { navigation } from '@espace-student/constants';
import { ChevronDownIcon } from '@adersolutions/icons';
import { getFilePath, isActive } from '@shared/utils';
import { useApp } from '@shared/stores';
import { ItemImage } from '@common/components';
import { useNotifications } from '@espace-student/stores';
const { user, drawer } = useApp();
const { count } = useNotifications();
</script>
