<template>
    <PageContainer class="bg-gray-100">
        <div class="overflow-hidden py-24 sm:py-24 h-full">
            <div v-if="page?.is_active" class="mx-auto max-w-7xl md:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:grid-cols-2 lg:items-start">
                    <div class="rounded-md">
                        <div
                            class="relative rounded-xl isolate overflow-hidden bg-primary"
                        >
                            <div
                                class="absolute -inset-y-px -left-3 -z-10 w-full origin-bottom-left skew-x-[-30deg] bg-white opacity-20 ring-1 ring-inset ring-white"
                                aria-hidden="true"
                            />
                            <div class="mx-auto max-w-2xl sm:mx-0 sm:max-w-none">
                                <img
                                    :src="page.extra?.image"
                                    :alt="page.title"
                                    width="2432"
                                    height="1442"
                                    class=" w-full object-contain rounded-xl bg-gray-800"
                                />
                            </div>
<!--                            <div-->
<!--                                class="pointer-events-none absolute inset-0 ring-1 ring-inset ring-black/10 sm:rounded-3xl"-->
<!--                                aria-hidden="true"-->
<!--                            />-->
                        </div>
                    </div>
                    <div class="px-6 lg:px-0 lg:pr-4">
                        <div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-lg space-y-5 ">
                            <div class="text-pretty text-xl font-semibold tracking-tight text-gray-900 sm:text-3xl">
                                <h1>
                                    {{ page.title }}
                                </h1>
                                <h3 class="text-primary font-light">
                                    {{ page.extra.subtitle }}
                                </h3>
                            </div>
                            <div v-html="page.extra?.description"></div>

                            <!-- Offers Selection -->
                            <!-- Features -->

                            <div class="">
                                <h3 class="text-base font-semibold text-gray-900">Choisir le Code désiré :</h3>
                                <RadioField
                                    v-model="selectedOfferId"
                                    :items="page.extra?.offers"
                                    :extra="(item:OfferType) => `Prix: ${moneyFormat(item.final_price)}`"
                                />
                            </div>
                            <div class="">
                                <h3 class="text-base font-semibold text-gray-900">Ce pack comprend :</h3>
                                <div class="mt-4 space-y-4" v-html="selectedOffer?.caracteristiques || ''"></div>
                                   <h3>La formation intensif se fait exclusivement en agence</h3>
                            </div>
                            <!-- Selected Offer Details -->
                            <div v-if="selectedOffer" class="mt-10">
                                <!-- Price -->
                                <div class="text-3xl font-bold text-gray-900">
                                    {{ moneyFormat(selectedOffer.final_price) }} TTC
                                    <span v-if="selectedOffer.discounted_price" class="ml-2 text-lg font-normal text-gray-500 line-through">
                                        {{ selectedOffer.original_price }}€ TTC
                                    </span>
                                </div>
                            </div>

                            <ClientButton
                                class="w-full"
                                :disabled="!selectedOffer || (selectedOffer && !!cart.isExist(selectedOffer))"
                                @click="() => selectedOffer && cart.add(selectedOffer)"
                                :loading="cart.state.loading[selectedOfferId]"
                            >
                                {{ selectedOffer && cart.isExist(selectedOffer) ? 'Déjà dans le panier' : 'Réserver cette offre' }}
                            </ClientButton>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="mx-auto max-w-lg">
                <h2 class="text-base font-semibold text-gray-900">Non code offert pour le moment</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Nous n'avons pas encore de code à vous offrir, mais nous travaillons dur pour vous en proposer bientôt.
                </p>
                <ul role="list" class="mt-6 divide-y divide-gray-200 border-b border-t border-gray-200">
                    <li v-for="(item, itemIdx) in items" :key="itemIdx">
                        <div class="group relative flex items-start space-x-3 py-4">
                            <div class="shrink-0">
                                <span :class="[item.iconColor, 'inline-flex size-10 items-center justify-center rounded-lg']">
                                    <component :is="item.icon" class="size-6 text-white" aria-hidden="true" />
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-900">
                                    <a :href="item.href">
                                        <span class="absolute inset-0" aria-hidden="true" />
                                        {{ item.name }}
                                    </a>
                                </div>
                                <p class="text-sm text-gray-500">{{ item.description }}</p>
                            </div>
                            <div class="shrink-0 self-center">
                                <span class="text-base text-gray-400 group-hover:text-gray-500" aria-hidden="true"> &rarr; </span>
                            </div>
                        </div>
                    </li>
                </ul>
                <div class="mt-6 flex">
                    <a href="/admin" class="text-sm font-medium text-primary hover:underline">
                        Ou connecter sur votre compte
                        <span aria-hidden="true"> &rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </PageContainer>
</template>

<script setup lang="ts">
import { ClientButton, PageContainer } from '@espace-client/components';
import { ref, computed } from 'vue';
import { RadioField } from '@shared/components';
import { moneyFormat } from '@shared/utils';
import { EmailFollowUpIcon, HomeIcon, StoreIcon } from '@adersolutions/icons';
import { useCart } from '@shared/stores';
import type { OfferType, PageType } from '@common/types';

type ExtraType = {
    description: string;
    price: string | null;
    image: string;
    subtitle: string;
    offers: OfferType[];
};

type PropsType = {
    page: PageType<ExtraType>;
};
const cart = useCart();

const props = defineProps<PropsType>();

const items = [
    {
        name: 'Accueil',
        description: 'Naviguer sur le site et découvrir nos offres.',
        href: '#',
        iconColor: 'bg-pink-500',
        icon: HomeIcon,
    },
    {
        name: 'Nos tarifs',
        description: "Consulter nos tarifs et choisir l'offre qui vous convient.",
        href: '#',
        iconColor: 'bg-purple-500',
        icon: StoreIcon,
    },
    {
        name: 'Contact',
        description: 'Nous contacter pour toute question ou assistance.',
        href: '#',
        iconColor: 'bg-yellow-500',
        icon: EmailFollowUpIcon,
    },
];
const selectedOfferId = ref(props.page.extra?.offers[0]?.id || '');

const selectedOffer = computed(() => {
    return props.page.extra?.offers.find((offer) => offer.id === selectedOfferId.value);
});
</script>
