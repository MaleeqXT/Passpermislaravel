<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { isActive } from '@shared/utils';
import { IdentityCardIcon, IdentityCardFilledIcon } from '@adersolutions/icons';
import { useApp } from '@shared/stores';
import { useScroll } from '@shared/hooks';
import type { NavigationItemType } from '@shared/types';
type PropsType = {
    navigation: NavigationItemType[];
};

defineProps<PropsType>();
const { drawer } = useApp();
const { show } = useScroll();
</script>

<template>
    <nav
        :class="[
            't-300 max-w-full fixed bottom-0 left-0 right-0 rounded-t-xl shadow-up z-20 md:hidden bg-dark bg-rainbow rainbow-opacity-30 backdrop-blur-[8px] text-white isolate unset',
            show ? 'translate-y-0' : 'translate-y-40',
        ]"
    >
        <div :key="$page.component" role="list" class="flex-center gap-0.5 p-1">
            <template v-for="item in navigation" :key="item.name">
                <Link
                    v-if="item.href"
                    :href="item.href"
                    :class="[
                        'flex-center flex-col flex-1 text-2xs p-0.5 rounded-lg t-200 relative',
                        isActive(item.href) ? '!text-primary' : 'active:bg-dark/10 active:text-dark ',
                    ]"
                >
                    <component :is="isActive(item.href) ? item.filled : item.icon" class="w-6 mt-px" />
                    {{ item.name }}
                </Link>
            </template>
            <button :class="['flex-center flex-col flex-1 text-2xs p-0.5 !rounded-2xl t-200 relative']" @click="drawer.open()">
                <IdentityCardIcon class="w-6 mt-px" />
                Profile
            </button>
        </div>
    </nav>
</template>
