<template>
    <footer class="bg-dark relative">
        <div class="mx-auto max-w-7xl px-6 pb-8 pt-16 sm:pt-24 lg:px-8 lg:pt-32">
            <div class="lg:grid lg:grid-cols-3 lg:gap-8">
                <div class="flex flex-col gap-10 mb-10">
                    <img class="h-14 w-fit object-contain" src="/assets/logo-permis-facile.webp" alt="pass permit facile" />
                    <ClientButton :href="route(routes.register.monitor.create)" variant="danger" class="w-fit">
                        Devenir un de nos moniteurs
                    </ClientButton>
                </div>

                <ul class="grid md:grid-cols-2 md:gap-4 col-span-2">
                    <li>
                        <h3 class="text-sm/6 font-semibold text-white">Informations légales</h3>
                        <ul role="list" class="md:mt-6 mt-3 space-y-1 md:space-y-4">
                            <li v-for="item in navigation.privacy" :key="item.name">
                                <template v-if="item.name !== 'Contrat de formation'">
                                    <component
                                        :is="item.external ? 'a' : Link"
                                        :target="item.external ? '_blank' : '_self'"
                                        :href="item.href"
                                        class="text-sm/6 text-gray-400 hover:text-white"
                                        >{{ item.name }}</component
                                    >
                                </template>
                                <template v-else>
                                    <button class="text-sm/6 text-gray-400 hover:text-white text-left" @click="isDropdownOpen = !isDropdownOpen">{{ item.name }}</button>
                                    <ul v-if="isDropdownOpen" class="mt-2 ml-4 space-y-1">
                                        <li>
                                            <a href="/assets/clients/docscpf.pdf" target="_blank" class="text-sm/6 text-gray-400 hover:text-white">Voici un exemple de contrat</a>
                                        </li>
                                        <li>
                                            <a href="/assets/clients/detail-des-formations.pdf" target="_blank" class="text-sm/6 text-gray-400 hover:text-white">Détail des formations</a>
                                        </li>
                                    </ul>
                                </template>
                            </li>
                        </ul>
                    </li>
                    <li class="mt-10 md:mt-0">
                        <h3 class="text-sm/6 font-semibold text-white">Nos Agences et Points de RDV</h3>
                      <ul role="list" class="md:mt-6 mt-3 space-y-1 md:space-y-4">
    <li
        v-for="item in navigation.adress"
        :key="item.name"
        class="flex gap-2"
    >
        <LocationIcon class="size-5 text-gray-400 mt-1" aria-hidden="true" />

        <div class="flex flex-col">
            <a
                :href="item.href"
                class="text-sm/6 text-gray-400 hover:text-white"
            >
                {{ item.name }}
            </a>

            <small class="text-xs text-gray-500">
                {{ item.agrement }}
            </small>
        </div>
    </li>
</ul>
                        <div class="text-white pt-5 space-y-2">
                            <p>Notre auto école est éligible au CPF</p>
                                                     <p>Numero d'activité :76311313631</p>

                        </div>
                    </li>
                </ul>
            </div>
            <!-- <div class="mt-16 border-t border-white/10 pt-8 sm:mt-20 lg:mt-24 lg:flex lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-sm/6 font-semibold text-white">Subscribe to our newsletter</h3>
                    <p class="mt-2 text-sm/6 text-gray-400">The latest news, articles, and resources, sent to your inbox weekly.</p>
                </div>
                <form class="mt-6 sm:flex sm:max-w-md lg:mt-0">
                    <label for="email-address" class="sr-only">Email address</label>
                    <input
                        type="email"
                        name="email-address"
                        id="email-address"
                        autocomplete="email"
                        required=""
                        class="w-full min-w-0 rounded-md bg-white/20 px-3 py-1.5 text-base text-white outline outline-1 -outline-offset-1 outline-gray-600 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:w-56 sm:text-sm/6"
                        placeholder="Enter your email"
                    />
                    <div class="mt-4 sm:ml-4 sm:mt-0 sm:shrink-0">
                        <button
                            type="submit"
                            class="flex w-full items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        >
                            Subscribe
                        </button>
                    </div>
                </form>
            </div> -->
            <div class="mt-8 border-t border-white/10 pt-8 md:flex md:items-center md:justify-between">
                <div class="flex gap-x-6 md:order-2">
                    <a v-for="item in navigation.social" :key="item.name" :href="item.href" class="text-gray-400 hover:text-gray-200">
                        <span class="sr-only">{{ item.name }}</span>
                        <component :is="item.icon" class="size-6" aria-hidden="true" />
                    </a>
                </div>
                <p class="mt-8 text-sm/6 text-gray-400 md:order-1 md:mt-0">
                    &copy; {{ new Date().getFullYear() }} PASSPERMISFACILE, Inc.Tous droits réservés
                </p>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { defineComponent, h } from 'vue';
import { ref } from 'vue';
import { LogoInstagramIcon, LogoFacebookIcon, LogoTiktokIcon, LogoYoutubeIcon, LogoSnapchatIcon, LocationIcon } from '@adersolutions/icons';
import { ClientButton } from '@espace-client/components';
import { routes } from '@espace-client/routes';
import { Link } from '@inertiajs/vue3';

const isDropdownOpen = ref(false);
const navigation = {
    privacy: [
        { name: 'Mentions Légales', href: route(routes.legal.mentions) },
        { name: 'politique de confidentialité & cookies', href: route(routes.legal.privacy) },
        { name: 'Conditions d’utilisation', href: route(routes.legal.terms) },
        { name: 'Conditions de vente', href: route(routes.legal.sales) },
        { name: 'règlement intérieur', href: '/assets/clients/Reglement-interieur-PASSPERMISFACILE-1.pdf', external: true },
        { name: 'Informations légales', href: '/assets/clients/Certification_Qualiopi_PASSEPERMISFACILE.pdf', external: true },
        { name: 'Contrat de formation', href: '/assets/clients/docscpf.pdf', external: true },
        { name: 'Détail des formations', href: '/assets/clients/newpdf.pdf', external: true },

    ],
adress: [
    {
        name: '139 Bd Déodat de Sévérac, 31300 Toulouse',
        agrement: 'Numéro d’agrément, Toulouse: E2303100100',
        href: '#',
    },

  {
        name: 'Boulevard André Netwiller, 31200 Toulouse',
        agrement: 'Numéro d’agrément, Toulouse: E2303100100',
        href: '#',
    },

    {
        name: '15 rue des Pierres 60100 Creil',
        agrement: 'Numéro d’agrément, Creil: E2406000100',
        href: '#',
    },
],
    social: [
        {
            name: 'Facebook',
            href: 'https://www.facebook.com/share/12GaNYhpqyT/?mibextid=wwXIfr',
            icon: LogoFacebookIcon,
        },
        {
            name: 'Instagram',
            href: 'https://www.instagram.com/passpermisfacile?igsh=aTk1Zmh3eDlnMzhv&utm_source=qr',
            icon: LogoInstagramIcon,
        },
        {
            name: 'Snapchat',
            href: 'https://snapchat.com/t/xB40qbIp',
            icon: LogoSnapchatIcon,
        },
        {
            name: 'Tiktok',
            href: 'https://www.tiktok.com/@passpermisfacile?_t=ZN-8wvzWkFW4ko&_r=1',
            icon: LogoTiktokIcon,
        },

        {
            name: 'YouTube',
            href: 'https://youtube.com/@passpermisfacile?si=_JLbSLMFIcXKi9kO',
            icon: LogoYoutubeIcon,
        },
    ],
};
</script>
