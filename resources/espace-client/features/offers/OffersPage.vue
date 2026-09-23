<template>
    <PageContainer>
        <div class="flex flex-col items-center justify-center">
            <img src="/assets/clients/nos-forfaits2.webp" alt="" class="h-16 w-auto mx-auto mt-20" />

            <p class="mt-4 text-lg text-gray-600">Découvrez nos offres et produits adaptés à tes besoins</p>

            <p v-if="agency" class="mt-2 text-md text-gray-600">
                <span class="font-bold text-gray-800"> Offres disponibles pour l'agence : {{ agency }} </span>
            </p>

            <p class="mt-4 text-md text-gray-600">Tous nos prix affichés sont en TTC. Le détail des formations sont disponible dans le footer.</p>

            <!-- Dynamic agency buttons -->
            <div class="mt-6 flex gap-4">
                <Link
                    v-for="ag in agencies"
                    :key="ag.id"
                    :href="route('offers.index', { is_auto: params.is_auto, agency: ag.slug })"
                    class="px-4 py-2 rounded-lg text-white font-bold"
                    :class="agency === ag.slug ? 'bg-blue-600' : 'bg-gray-400'"
                >
                    {{ ag.name }}
                </Link>
            </div>

            <!-- Tab Switch -->
            <TabSwitch
                v-model="params.is_auto"
                class="mt-6"
                size="xl"
                :items="tabItems"
                :active="params.is_auto"
                @change="params.set({ is_auto: $event })"
            />
        </div>

        <div class="mx-auto max-w-2xl px-4 py-4 sm:px-6 sm:py-5 lg:max-w-5xl lg:px-8">
            <!-- Loading skeleton -->
            <ul v-if="params.loading" class="mt-8 grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-3 xl:gap-x-8 pb-20">
                <li v-for="i in 3" :key="i" class="bg-white box p-5 overflow-hidden flex flex-col">
                    <span class="h-40 w-40 mx-auto bg-gray-300 animate-pulse rounded-lg"></span>
                    <span class="h-36 w-36 mx-auto bg-gray-300 animate-pulse rounded-full mt-5"></span>
                    <span class="h-6 rounded-lg bg-gray-300 animate-pulse mt-10"></span>
                    <span class="h-6 rounded-lg bg-gray-300 animate-pulse mt-4"></span>
                    <span class="h-6 rounded-lg bg-gray-300 animate-pulse mt-4"></span>
                    <span class="h-6 rounded-lg bg-gray-300 animate-pulse mt-4"></span>
                    <span class="h-10 rounded-lg bg-gray-300 animate-pulse mt-10"></span>
                </li>
            </ul>

            <!-- Offers list -->
            <ul v-else class="mt-8 grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-3 xl:gap-x-8 pb-20">
                <OfferCard v-for="offer in offers.data" :key="offer.id" :offer="offer" />
            </ul>
        </div>
    </PageContainer>
</template>

<script setup lang="ts">
import { PageContainer } from '@espace-client/components';
import OfferCard from './partials/OfferCard.vue';
import { DataListType } from '@shared/types';
import { OfferType } from '@common/types';
import { TabSwitch } from '@shared/components';
import { useRoute } from '@shared/hooks';
import { computed } from 'vue';

type PropsType = {
    offers: DataListType<OfferType>;
    agency?: string; // Agency name sent from Laravel
};

// Props from controller
const props = defineProps<PropsType>();
const params = useRoute<{ is_auto: number }>();

// Tab items (always fixed)
const tabItems = [
    { name: 'Formation en boîte manuelle', id: 0, class: 'danger' },
    { name: 'Formation en boîte Automatique', id: 1, class: 'info' },
];

// Agency name for display
const agency = computed(() => props.agency ?? null);
</script>
