<template>
    <div class="hidden lg:z-50 md:flex lg:flex-col">
        <nav :class="['flex items-center mx-auto w-full py-2 border-b border-white/10', settings.width]">
            <div class="flex py-0.5 flex-1">
                <img class="h-8 object-contain px-4" src="/assets/logo.svg" alt="Logo" />
            </div>
            <div :key="$page.component" role="list" class="flex gap-2 max-md:justify-center justify-end px-2">
                <template v-for="item in navigation" :key="item.name">
                    <component
                        :is="item.href ? Link : item.external ? 'a' : 'button'"
                        :href="item.href || '#'"
                        :class="[
                            'group flex-center gap-x-2 rounded-lg  h-10  px-3 text-sm leading-6 font-semibold cursor-pointer t-200',
                            isActive(item.href) ? 'btn-header ' : 'text-gray-100 hover:text-primary',
                        ]"
                        @click="item.drawer ? drawer.open() : () => {}"
                    >
                        <component
                            :is="item.icon"
                            :class="[isActive(item.href) ? 'text-white' : 'text-slate-300 group-hover:text-primary', 'h-5 w-5 shrink-0 ']"
                            aria-hidden="true"
                        />
                        <span class="flex-1 leading-none">{{ item.name }}</span>
                    </component>
                </template>
                <button
                    :class="[
                        'group flex-center gap-x-2 rounded-lg  h-10 text-slate-300 px-3 text-sm leading-6 font-semibold cursor-pointer t-200',
                    ]"
                    @click="drawer.open()"
                >
                    <IdentityCardIcon class="w-6 mt-px" />
                    Profile
                </button>
            </div>
        </nav>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { navigation } from '@espace-student/constants';
import { IdentityCardIcon } from '@adersolutions/icons';
import { isActive } from '@shared/utils';
import { useApp } from '@shared/stores';
const { settings, drawer } = useApp();
</script>
