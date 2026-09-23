<template>
    <div class="contents">
        <span v-if="impersonate" :class="['h-[52px] md:h-[61px] block w-full t-5', hide ? '-mt-[52px] md:-mt-[61px]' : 'mt-0']"></span>
        <div
            v-if="impersonate"
            :class="[
                'flex gap-3 items-center bg-black text-white t-5 fixed inset-x-0 py-2 z-[899] border-b border-gray-500/30',
                hide ? '-top-[53px] md:-top-[61px]' : 'top-0',
            ]"
        >
            <div class="flex items-center py-0.5 w-72 border-r border-gray-500/50 max-md:hidden text-2xl font-bold px-5 gap-3">
                <img class="h-10 object-contain" src="/assets/logo.svg" alt="Logo" />
                <span> Easy<span class="text-primary"> moniteur</span> </span>
            </div>
            <div class="flex items-center justify-between gap-3 flex-1 px-3 leading-4">
                <ItemImage :src="getFilePath(impersonate)" :title="impersonate?.name" content="Imiter" size="w-9 h-9" />
                <a class="btn btn-primary btn-link" :href="route(routes.impersonate.stop)">Quitter</a>
            </div>
            <ChevronDownIcon
                class="w-16 h-5 px-1 -mt-px absolute btn-m left-1/2 -translate-x-1/2 top-full bg-dark rounded-b-lg border-x border-b border-gray-500/90"
                @click="setHide(!hide)"
            />
        </div>
    </div>
</template>
<script setup lang="ts">
import { useApp } from '@shared/stores';
import { ChevronDownIcon } from '@adersolutions/icons';
import ItemImage from './ItemImage.vue';
import { getFilePath } from '@shared/utils';
import { routes } from '@espace-admin/routes';
import { useStorage } from '@shared/hooks';
const { impersonate } = useApp();
const [hide, setHide] = useStorage('impersonate_banner_hide');
</script>
