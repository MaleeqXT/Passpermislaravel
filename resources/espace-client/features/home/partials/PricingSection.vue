<template>
    <div v-if="canSeeOffers" class="isolate overflow-hidden bg-white">
        <div class="mx-auto max-w-7xl px-6 pb-96 pt-24 text-center sm:pt-32 lg:px-8">
            <div class="mx-auto max-w-4xl">
                <img src="/assets/clients/meilleurs-packs-1.webp" alt="" class="mx-auto max-w-md w-full" />
            </div>
            <div class="relative mt-6">
<!--                <p class="mx-auto max-w-2xl text-pretty text-lg font-medium text-gray-600 sm:text-xl/8">-->
<!--                    Choose an affordable plan that’s packed with the best features for engaging your audience, creating customer loyalty,-->
<!--                    and driving sales.-->
<!--                </p>-->
                <svg
                    viewBox="0 0 1208 1024"
                    class="absolute -top-10 left-1/2 -z-10 h-[64rem] -translate-x-1/2 [mask-image:radial-gradient(closest-side,white,transparent)] sm:-top-12 md:-top-20 lg:-top-12 xl:top-0"
                >
                    <ellipse cx="604" cy="512" fill="url(#6d1bd035-0dd1-437e-93fa-59d316231eb0)" rx="604" ry="512" />
                    <defs>
                        <radialGradient id="6d1bd035-0dd1-437e-93fa-59d316231eb0">
                            <stop stop-color="#86be554d" />
                            <stop offset="1" stop-color="#86be55" />
                        </radialGradient>
                    </defs>
                </svg>
            </div>
        </div>
        <div class="flow-root bg-white pb-24 sm:pb-32">
            <div class="-mt-80">
<ul class="mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 max-w-md gap-3 md:gap-5 lg:max-w-screen-lg max-md:px-5">
                    <OfferCard v-for="offer in offers" :key="offer.id" :offer="offer" class="slider-item" />
                </ul>
                <div class="max-md:px-5 pt-8 lg:max-w-screen-lg mx-auto w-full">
                    <div
                        class="flex flex-col bg-rainbow rainbow-opacity-30 items-start gap-x-8 gap-y-6 rounded-3xl p-8 ring-1 ring-gray-900/10 sm:gap-y-10 sm:p-10"
                    >
                        <div class="lg:min-w-0 lg:flex-1">
                            <h3 class="text-base/7 font-semibold text-primary">Offres supplémentaires</h3>
                            <p class="mt-1 text-base/7 text-gray-600">
                                Découvre plus d’offres pour cette section et profite de nos promotions exclusives.
                            </p>
                        </div>
                        <ClientButton :href="route(routes.offers)" variant="outline" class="max-md:w-full">
                            voir toutes les offres
                            <span aria-hidden="true">&rarr;</span>
                        </ClientButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ClientButton } from '@espace-client/components';
import OfferCard from '@espace-client/features/offers/partials/OfferCard.vue';
import { routes } from '@espace-client/routes';
import { useQuery } from '@shared/hooks';
import { computed } from 'vue';
import { useApp } from '@shared/stores';

const pageQuery = useQuery(
    {
        url: route(routes.api.pages.home),
        dataType: {},
    },
    true
);

const offers = computed(() => pageQuery.data?.offers);

const { isLogged, user } = useApp();

const canSeeOffers = computed(() => {
    // Show offers to guests (not logged in)
    if (!isLogged) {
        return true;
    }

    if (!user) {
        return true;
    }

    // Show offers to SuperAdmin or admin@pf.com
    if (user.name === 'SuperAdmin' || user.email === 'admin@pf.com') {
        return true;
    }

    // Show offers to students from Creil/Toulouse only
    const ville = String(user.ville || '').toLowerCase().trim();
    const postal = String(user.postal || '').trim();

    const isCreil = ville.includes('creil') || postal === '60100';
    const isToulouse = ville.includes('toulouse') || postal === '31300';

    return isCreil || isToulouse;
});

</script>
