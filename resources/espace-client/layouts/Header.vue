<script setup lang="ts">
import { reactive, ref , computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { routes } from '@espace-client/routes';
import { useApp, useCart } from '@shared/stores';
import { getFilePath, isActive } from '@shared/utils';
// import { CartIcon } from '@heroicons/vue/20/solid';
import { MenuIcon, XIcon, CartFilledIcon, PersonFilledIcon } from '@adersolutions/icons';
import { Drawer } from '@shared/components';
import { CartDrawer, ClientButton } from '@espace-client/components';
const cart = useCart();
const { isLogged, user, logout } = useApp();

const state = reactive({
    menu: false,
    cart: false,
});

const desktopDropdown = ref<string | null>(null)
const mobileDropdown = ref<string | null>(null)

const forfaitChildren = [
    { name: 'Agence Toulouse', href: route('offers.index', { is_auto: 1, agency: 'toulouse' }) },
    { name: 'Agence Creil', href: route('offers.index', { is_auto: 0, agency: 'creil' }) },
];


const filteredForfaitChildren = computed(() => {

    // agar login nahi → dono show
    if (!isLogged || !user?.ville) {
        return forfaitChildren;
    }

    // ✅ SECRETARIES see all agencies
    if ((user as any)?.is_secretary) {
        return forfaitChildren;
    }

    const ville = user.ville.toLowerCase();

    if (ville.includes('creil')) {
        return forfaitChildren.filter(c =>
            c.name.toLowerCase().includes('creil')
        );
    }

    if (ville.includes('toulouse')) {
        return forfaitChildren.filter(c =>
            c.name.toLowerCase().includes('toulouse')
        );
    }

    // ⚡ agar koi aur ville hai → kuch bhi na show ho
    return [];
});


const navigation = [
    {
        name: 'Accueil',
        href: route(routes.home),
    },
       {
        name: 'Nos forfaits',
        children: filteredForfaitChildren.value,
    },

    { name: 'Code', href: route(routes.code) },
    { name: 'CPF', href: route(routes.CPF) },
    { name: 'Nos agences', href: route(routes.agencies) },
    { name: 'Nos Vidéos', href: route(routes.videos) },
    { name: 'Contact ', href: route(routes.contact.index) },
];
</script>

<template>

    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="line-h absolute inset-x-0 bottom-0"></div>
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-3 py-1.5 lg:px-8 -mb-px" aria-label="Global">
            <Link :href="route(routes.home)" class="flex lg:flex-1">
                <span class="sr-only">Your Company</span>
                <img class="h-10 md:h-14 w-auto" src="/assets/logo-permis-facile.webp" alt="" />
            </Link>

<!-- DESKTOP NAV -->
<div class="hidden md:flex items-center gap-x-2 relative">

    <div
        v-for="item in navigation"
        :key="item.name"
        class="relative"
    >
        <!-- NORMAL LINK -->
       <Link
    v-if="!item.children"
    :href="item.href"
    class="h-9 flex items-center text-sm px-4 rounded-lg font-medium hover:bg-dark2/5"
>
    {{ item.name }}
</Link>


        <!-- CLICK DROPDOWN -->
        <div v-else>
          <button
    class="h-9 flex items-center gap-1 text-sm px-4 rounded-lg font-medium hover:bg-dark2/5"
    @click="desktopDropdown === item.name
        ? desktopDropdown = null
        : desktopDropdown = item.name"
>
    {{ item.name }}
    <span class="text-xs translate-y-[1px]">▾</span>
</button>


          <div
    v-show="desktopDropdown === item.name"
    class="absolute left-0 top-[calc(100%+6px)] bg-white shadow-lg rounded-md w-48 z-50"
>

                <Link
                    v-for="child in item.children"
                    :key="child.name"
                    :href="child.href"
                    class="block px-4 py-2 text-sm hover:bg-primary/10"
                >
                    {{ child.name }}
                </Link>
            </div>
        </div>
    </div>
</div>




            <div class="flex lg:flex-1 lg:justify-end items-center gap-2 md:gap-4">
                <a
                    v-if="isLogged"
                    :href="'/admin'"
                    class="flex gap-2 justify-center items-center rounded-lg text-sm font-semibold leading-6 text-dark"
                >
                    <!-- <span> Mon compte</span> -->
                    <img :src="getFilePath(user)" class="w-10 bg-gray-100 rounded-lg" />
                </a>
                <Link
                    v-else
                    :href="route(routes.login)"
                    class="text-sm/6 font-semibold text-dark2 md:bg-dark2/10 hover:bg-dark2/20 md:px-4 md:py-1.5 md:ring-2 ring-dark2 rounded-lg text-nowrap"
                >
                    <span class="max-md:hidden"> Se connecter &rarr;</span>
                    <PersonFilledIcon class="w-10 h-10 p-1.5 text-dark2 md:hidden" />
                </Link>
                <button @click="state.cart = !state.cart" class="relative">
                    <span
                        v-if="cart.count"
                        class="absolute -top-1 -left-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-medium text-white"
                    >
                        {{ cart.count }}
                    </span>
                    <CartFilledIcon
                        class="lg:justify-end w-10 h-10 text-dark2 rounded-lg p-1.5 cursor-pointer"
                        @click="$emit('openCart')"
                    />
                </button>
                <!-- <span class="hidden max-md:inline">Se connecter</span> -->
                <div class="lg:hidden w-10 flex-center">
                    <button
                        type="button"
                        class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700"
                        @click="state.menu = true"
                    >
                        <span class="sr-only">Open main menu</span>
                        <MenuIcon class="size-6" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </nav>
        <Drawer @close="state.menu = false" :show="state.menu">
            <div class="flex flex-col p-3 h-full">
                <div class="flex items-center justify-between">
                    <Link :href="route(routes.home)" class="flex lg:flex-1">
                        <span class="sr-only">pass permit facile</span>
                        <img class="h-10 md:h-14 w-auto" src="/assets/logo-permis-facile.webp" alt="pass permit facile" />
                    </Link>



                    <button type="button" class="block text-gray-700" @click="state.menu = false">
                        <XIcon class="size-6" aria-hidden="true" />
                    </button>
                </div>
                  <div class="mt-6 divide-y divide-gray-200 flex flex-col">
    <div
        v-for="item in navigation"
        :key="item.name"
        class="py-3"
    >
        <!-- NORMAL LINK -->
        <Link
            v-if="!item.children"
            :href="item.href"
            class="block text-base"
            @click="state.menu = false"
        >
            {{ item.name }}
        </Link>

        <!-- MOBILE DROPDOWN -->
        <div v-else>
            <button
                class="w-full flex justify-between items-center text-base"
                @click="mobileDropdown === item.name
                    ? mobileDropdown = null
                    : mobileDropdown = item.name"
            >
                {{ item.name }} <span>▾</span>
            </button>

            <div
                v-show="mobileDropdown === item.name"
                class="mt-2 ml-4 flex flex-col gap-2"
            >
                <Link
                    v-for="child in item.children"
                    :key="child.name"
                    :href="child.href"
                    class="text-sm text-gray-600"
                    @click="state.menu = false"
                >
                    {{ child.name }}
                </Link>
            </div>
        </div>
    </div>
</div>

                <ClientButton v-if="isLogged" variant="danger" @click="logout" class="px-3 py-2 flex-center"> Se déconnecter </ClientButton>
                <ClientButton v-else variant="primary" :href="route(routes.login)" class="px-3 py-2 flex-center">
                    Se connecter
                </ClientButton>
            </div>
        </Drawer>
        <CartDrawer v-model="state.cart" />
    </header>
</template>
