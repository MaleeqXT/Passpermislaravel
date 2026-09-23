
<script setup lang="ts">
import { ClientButton } from '@espace-client/components';
import { routes } from '@espace-client/routes';
import { ChevronRightIcon } from '@heroicons/vue/20/solid';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useApp } from '@shared/stores';
// import { Link } from '@inertiajs/vue3';

const { isLogged, user } = useApp();

const canSeeOffersLink = computed(() => {
    // Show link to guests (not logged in)
    if (!isLogged) {
        return true;
    }

    if (!user) {
        return true;
    }

    // Show link to SuperAdmin or admin@pf.com
    if (user.name === 'SuperAdmin' || user.email === 'admin@pf.com') {
        return true;
    }

    // Show link to students from Creil/Toulouse only
    const ville = String(user.ville || '').toLowerCase().trim();
    const postal = String(user.postal || '').trim();

    const isCreil = ville.includes('creil') || postal === '60100';
    const isToulouse = ville.includes('toulouse') || postal === '31300';

    return isCreil || isToulouse;
});
</script>
<template>
    <section class="relative isolate overflow-hidden bg-gradient-to-b from-indigo-100/20">
        <div class="clippath"></div>

        <div class="mx-auto max-w-7xl pb-24 pt-10 sm:pb-32 lg:grid lg:grid-cols-2 lg:gap-x-8 lg:px-8">
            <div class="px-5 lg:px-0 lg:pt-4">
                <div class="mx-auto max-w-2xl">
                    <div class="max-w-lg">
                        <div class="mt-5 sm:mt-32 lg:mt-24">
                            <div class="inline-flex space-x-6">
                                <span
                                    class="rounded-full bg-red-600/10 px-3 py-1 text-sm/6 font-semibold text-red-500 ring-1 ring-inset ring-red-600/10"
                                    >Quoi de neuf</span
                                >
                                <Link
                                    v-if="canSeeOffersLink"
                                    :href="route(routes.offers)"
                                    class="inline-flex items-center space-x-2 text-sm/6 font-medium text-gray-600"
                                >
                                    <span>Voir nos offres </span>
                                    <ChevronRightIcon class="size-5 text-gray-400" aria-hidden="true" />
                                </Link>
                            </div>
                        </div>
                        <img class="w-full object-contain" src="/assets/clients/passer-le-permis2.webp" alt="passe le permit" />

                        <p class="mt-8 text-pretty text-lg font-medium text-gray-700 sm:text-xl/8">
                            Apprendre à conduire n’a jamais été aussi simple avec PassPermisFacile. Des cours en ligne sur le code de la route et une expérience pratique sur mesure adaptée à ton emploi du temps. Profite sans plus attendre de nos forfaits très abordables.
                        </p>
                        <div class="mt-10 flex items-center max-md:flex-col gap-y-3 gap-x-6">
                            <ClientButton class="max-md:w-full" :href="route(routes.register.student.create)">
                                Inscription gratuite
                            </ClientButton>
                            <ClientButton
                                variant="link-dark"
                                :href="route(routes.contact.index)"
                                class="font-semibold text-gray-900 text-lg max-md:bg-white max-md:shadow-sm border-dark2 max-md:border-b-4 max-md:w-full"
                                >Nous contacter <span class="ml-2">&rarr;</span>
                            </ClientButton>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-20 sm:mt-24 md:mx-auto md:max-w-2xl lg:mx-0 lg:mt-0 lg:w-screen">
                <div class="relative px-6 md:pl-16 md:pr-0">
                    <div class="mx-auto max-w-2xl md:mx-0 md:max-w-none">
                        <img src="/assets/clients/Hero-passpermis.png" alt="" />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

