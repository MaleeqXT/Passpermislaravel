<template>
    <div
        id="secretary-sidebar"
        :class="[
            'sticky top-0 flex flex-col gap-y-3 w-full z-10 overflow-y-auto transition-all ease-linear duration-500 rounded-tl-xl pt-3 lg:w-60 max-lg:hidden h-[100vh]',
            sidebar.isCollapsed ? 'max-w-[60px] sidebar-collapsed' : 'max-w-full',
        ]"
    >
        <div class="v-rainbow -mr-1 absolute left-0"></div>
        <div class="v-rainbow absolute right-px"></div>
        <!-- <div class=""> -->
        <Popup class="max-md:hidden mx-3">
            <div class="btn-header text-white btn-m flex w-full gap-3 !rounded-lg !p-1 !h-auto">
                <img class="h-8 w-8 rounded-lg" src="/assets/logo.svg" alt="logo" />
                <p v-if="!sidebar.isCollapsed" class="flex-1 leading-5">
                    <span class="block text-sm">
                        {{ user?.name }}
                    </span>
                    <span class="text-xs text-green-400 flex -ml-1.5">
                        <BulletIcon class="h-6 w-6 -m-[3px] text-green-400" />
                        Active
                    </span>
                </p>
            </div>

            <template #content>
                <ul class="flex flex-col w-full text-sm min-w-48">
                    <li>
                        <a href="/" target="_blank" class="p-2 btn-m hover:bg-white/10 flex gap-2" rel="noopener noreferrer">
                            <ViewIcon class="h-5 w-5 text-gray-500" />
                            Voir site
                        </a>
                    </li>
                    <li class="p-2 btn-m hover:bg-white/10 flex gap-2" @click="drawer.open()">
                        <SettingsIcon class="h-5 w-5 text-gray-500" />
                        Parametres
                    </li>
                    <li class="p-2 btn-m hover:bg-white/10 border-t border-white/20 flex gap-2 text-red-500" @click="logout()">
                        <ExitIcon class="h-5 w-5 text-red-500" />
                        Deconnexion
                    </li>
                </ul>
            </template>
        </Popup>

        <!-- </div> -->
        <nav class="flex flex-1 flex-col">
            <ul :key="$page.component" role="list" class="flex flex-1 flex-col text-dark/80">
                <NavMenu v-for="(item, idx) in filteredNavigation" :key="idx + item.name" :item="item" />
                <NavMenu v-for="(item, idx) in storedNavigation" :key="idx + item.name" :item="item" />
                <li class="px-3 flex-center text-2xs bg-white-block btn-m mx-3 py-1 rounded-md mt-1" @click="onAddLink">
                    <PlusIcon class="h-5 w-5 shrink-0 fill-current" aria-hidden="true" />
                    <span v-if="!sidebar.isCollapsed"> Ajouter un lien</span>
                </li>
            </ul>
            <span v-show="!sidebar.isCollapsed" class="mt-auto text-2xs text-white/70 flex-center p-1">
                All rights reserved &copy; {{ new Date().getFullYear() }}
            </span>
        </nav>

        <CreateNav :show="state.showCreateNav" @close="onClose" />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, reactive, computed } from 'vue';
import { NavMenu, Popup } from '@shared/components';
import { useStorage } from '@shared/hooks';
import { useApp, useSidebar } from '@shared/stores';
import { headerNavigation, navigation } from '@espace-secretary/enums';
import { ViewIcon, SettingsIcon, ExitIcon, PlusIcon, BulletIcon } from '@adersolutions/icons';
import CreateNav from './CreateNav.vue';
const { drawer, user, logout } = useApp();
const sidebar = useSidebar();
const [links] = useStorage<any[]>('HEADER:LINKS', []);

const storedNavigation = ref([
    {
        name: 'Liens personnalisés',
        divider: true,
    },
    ...headerNavigation,
    ...(links.value ?? []),
]);

// Determine if current user is admin
const isAdmin = computed(() => {
    const u: any = user;
    if (!u) return false;
    // Check roles array
    if (Array.isArray(u.roles) && u.roles.some((r: any) => (r.name || r) === 'admin')) return true;
    // Check role object
    if (u.role && ((u.role.name && u.role.name.toLowerCase() === 'admin') || u.role_id === 1)) return true;
    // Check numeric role id
    if (u.role_id === 1 || u.role === 1) return true;
    return false;
});

const filteredNavigation = computed(() => {
    const filterItem = (item: any) => {
        if (!item) return false;
        if (!item.roles || item.roles.length === 0) return true;
        // item.roles provided, allow if any role matches
        return item.roles.some((r: string) => r === 'admin') ? isAdmin.value : true;
    };

    const mapItems = (items: any[]) =>
        (items || [])
            .map((it) => {
                if (!filterItem(it)) return null;
                const copy = { ...it };
                if (copy.children) copy.children = mapItems(copy.children).filter(Boolean);
                return copy;
            })
            .filter(Boolean);

    return mapItems(navigation);
});
const state = reactive({
    showCreateNav: false,
    isCollapsed: false,
});
onMounted(() => {
    const sidebar = document.querySelector('#secretary-sidebar');
    if (sidebar) {
        let previousScroll = window.scrollY;
        document.addEventListener('scroll', () => {
            const currentScroll = window.scrollY;
            sidebar.scrollBy(0, currentScroll - previousScroll);
            previousScroll = currentScroll;
        });
    }
});
const onAddLink = () => {
    state.showCreateNav = true;
};
const onClose = (newLink: any) => {
    state.showCreateNav = false;
    if (newLink) {
        storedNavigation.value = [...storedNavigation.value, newLink];
    }
};
</script>
