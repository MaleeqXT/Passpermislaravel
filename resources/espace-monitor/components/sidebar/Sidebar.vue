<template>
    <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-64 lg:flex-col text-white/70">
        <div class="flex grow flex-col gap-y-3 overflow-y-auto h-full">
            <div class="flex py-3 px-5 gap-3 items-center text-2xl font-bold">
                <img class="h-10 object-contain" src="/assets/logo.svg" alt="Logo" />
                <span> Easy<span class="text-primary"> monitor</span> </span>
            </div>
            <nav class="flex flex-1 flex-col px-4">
                <ItemImage
                    :src="getFilePath(user)"
                    size="w-10 h-10"
                    class="btn-header h-12 mb-5 w-full bg-rainbow rainbow-opacity-30"
                    :title="user.name"
                    @click="drawer.open()"
                />
                <ul :key="$page.component" role="list" class="flex flex-1 flex-col gap-1">
                    <template v-for="item in navigation" :key="item.name">
                        <!-- <li v-if="item.grow" class="flex-1"></li> -->
                        <li>
                            <component
                                :is="item.href ? Link : 'button'"
                                :href="item.href || '#'"
                                :class="[
                                    'group flex items-center !justify-start gap-x-2 rounded-xl h-12 px-3 text-base w-full font-semibold cursor-pointer t-200',
                                    item.href && isActive(item.href) ? 'btn-header font-bold ' : ' hover:bg-white/10',
                                ]"
                                @click="item.drawer ? drawer.open() : () => {}"
                            >
                                <component
                                    :is="item.icon"
                                    :class="[item.href && isActive(item.href) ? 'text-dark bg-white' : '', 'h-6 w-6 shrink-0 rounded-full']"
                                    aria-hidden="true"
                                />
                                <span class="flex-1 leading-none mb-0.5 text-left">{{ item.name }}</span>
                            </component>
                        </li>
                    </template>
                </ul>
            </nav>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { navigation } from '@espace-monitor/constants';
import { getFilePath, isActive } from '@shared/utils';
import { useApp } from '@shared/stores';
import { ItemImage } from '@common/components';
const { user, drawer } = useApp();
</script>
