<script setup lang="ts">
import { Button, Card, Thumb } from '@shared/components';
import { getFilePath, moneyFormat } from '@shared/utils';
import { useCart } from '@shared/stores';
import { SizeEnum } from '@shared/enums';
import { OffreTypeList } from '@common/enums';
import { OfferType } from '@common/types';
import { NotificationFilledIcon } from '@adersolutions/icons';
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';


const props = defineProps<{ item: OfferType }>();
const cart = useCart();
const page = usePage();

// Get user's agency based on city (same logic as useCart store)
const getUserAgency = (): string => {
    const user = (page.props.auth as any)?.user;
    if (!user) return 'criel'; // guest fallback to criel

    const ville = String(user.ville ?? '').toLowerCase();

    if (ville.includes('creil')) return 'criel';
    if (ville.includes('toulouse')) return 'toulouse';

    return 'criel'; // default
};

const agencyPricing = computed(() => {
    if (!props.item?.agency_pricing) return null;

    let pricingList =
        typeof props.item.agency_pricing === 'string'
            ? JSON.parse(props.item.agency_pricing)
            : props.item.agency_pricing;

    if (!Array.isArray(pricingList) || !pricingList.length) return null;

    // Get user's agency
    const userAgency = getUserAgency();

    // Find matching agency pricing, fallback to first
    return pricingList.find(
        (p: any) => String(p.agency ?? '').toLowerCase() === userAgency
    ) ?? pricingList[0];
});


const installments = computed(() => {
    return agencyPricing.value?.installments || [];
});

const firstInstallmentAmount = computed(() => {
    const first = installments.value.find(i => Number(i.no) === 1);
    return first ? Number(first.amount) : null;
});

const originalPrice = computed(() => {
    return agencyPricing.value?.original_price ?? props.item?.final_price ?? 0;
});

// Effective multi-payment: prefer agency-specific setting, fallback to item property
const effectiveMultiPayment = computed(() => {
    return agencyPricing.value?.multi_payment ?? props.item?.multi_payment ?? 1;
});

// ✅ Modal state
const showDetails = ref(false);
const openDetails = () => (showDetails.value = true);
const closeDetails = () => (showDetails.value = false);

// ✅ ONLY for these 2 offers: "Conduite supevisée" and "Pass Permis à la carte"
const shouldOpenModal = computed(() => {
    const name = String(props.item?.name ?? '').toLowerCase();
    const isConduiteSup = name.includes('conduite supevisée');
    const isPassPermis = name.includes('pass permis à la carte');
    return isConduiteSup || isPassPermis;
});

// ✅ Get agency caracteristiques
const getAgencyCaracteristiques = () => {
    const pricing = agencyPricing.value;
    return pricing?.caracteristiques ?? props.item?.caracteristiques ?? '';
};

// ✅ Parse services from HTML
const parseServices = (htmlDescription: string) => {
    if (!htmlDescription) return [];
    const cleaned = htmlDescription.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').replace(/&euro;/g, '€').replace(/\s+/g, ' ').trim();
    const services: { id: string; name: string; price: number; hours?: number }[] = [];

    const hoursRegex = /(\d+(?:[.,]\d+)?)\s*[Hh]\b/g;
    const hoursMatches: Array<{ idx: number; value: number }> = [];
    let hm: RegExpExecArray | null;
    while ((hm = hoursRegex.exec(cleaned)) !== null) {
        const v = parseFloat(hm[1].replace(',', '.'));
        if (!isNaN(v)) hoursMatches.push({ idx: hm.index, value: v });
    }

    const globalRegex = /(.+?)[\s:=]*([0-9]+(?:[.,][0-9]+)?)\s*€/g;
    let match: RegExpExecArray | null;
    while ((match = globalRegex.exec(cleaned)) !== null) {
        let name = match[1].trim().replace(/^->\s*/, '').replace(/^\d+\.\s*/, '');
        const priceStr = match[2].replace(',', '.');
        const price = parseFloat(priceStr);
        if (!name || name.length < 2 || !price || price <= 0) continue;

        const numericPart = match[2];
        const priceIdx = match.index + (match[0].indexOf(numericPart) >= 0 ? match[0].indexOf(numericPart) : 0);
        const matchEnd = match.index + match[0].length;
        let nearest: { idx: number; value: number } | null = null;
        let bestDist = Infinity;
        const candidateHours = hoursMatches.filter(h => h.idx < matchEnd);
        for (const h of candidateHours) {
            const d = Math.abs(h.idx - priceIdx);
            if (d < bestDist && d <= 40) {
                bestDist = d;
                nearest = h;
            }
        }

        services.push({ id: `svc-${services.length}`, name, price, hours: nearest?.value });
    }
    return services;
};

const services = computed(() => parseServices(getAgencyCaracteristiques()));

// ✅ Add service to cart
const addServiceToCart = (service: any) => {
    const offer = {
        ...props.item,
        override_price: true,
        final_price: service.price,
        service_label: service.name,
        caracteristiques: service.name,
        balance: service.hours ?? 0,
        multi_payment: 1
    };
    cart.add(offer, () => closeDetails());
};

</script>

<template>
    <li class="grid grid-cols-1 rounded-xl shadow-box ring-1 ring-black/5 max-lg:mx-auto max-lg:w-full max-lg:max-w-md">
        <Card padding="lg">
          <div
    v-if="effectiveMultiPayment > 1 && (firstInstallmentAmount ?? (originalPrice / (effectiveMultiPayment || 1))) > 0"
    class="text-sm text-dark bg-gradient-to-r from-rose-600/20 to-primary/20 bg-rainbow rounded-lg p-2 flex gap-2 items-center mb-4"
>
    <NotificationFilledIcon class="w-9 h-9 p-2 rounded-lg bg-rose-500/15 text-rose-600" />
    <p class="mb-px ml-1">
        Possible de paiement en <b>{{ effectiveMultiPayment }}</b> fois
        <b class="text-md block">
            {{ moneyFormat(firstInstallmentAmount ?? (originalPrice / (effectiveMultiPayment || 1))) }}
        </b>
    </p>
</div>

            <Thumb :src="getFilePath(item, true)" :size="SizeEnum.MD" />

            <p class="mt-2 text-pretty text-sm/6 text-gray-600">{{ item.name }}</p>

            <p class="mt-2 text-pretty text-sm/6 text-gray-600">{{ item.description }}</p>
            <div v-if="originalPrice > 0" class="mt-5 flex items-end gap-4">
                <div class="text-3xl font-semibold text-gray-950">{{ moneyFormat(originalPrice) }}</div>
                <div v-if="item?.discounted_price" class="text-lg line-through text-gray-950 mb-px opacity-70">
                    {{ moneyFormat(item?.discounted_price) }}
                </div>
            </div>
            <div class="mt-5">
                <!-- Modal button for special offers: always visible to allow multiple selections -->
                <Button
                    v-if="shouldOpenModal"
                    :variant="cart.isExist(item) ? 'secondary' : 'warning'"
                    class="h-10 px-5 w-full"
                    :disabled="cart.mutation.mutating || cart.query.fetching"
                    :loading="cart.state.loading[item.id]"
                    @click="cart.isExist(item) ? cart.remove(item) : openDetails()"
                >
                    {{ cart.isExist(item) ? 'Retirer et choisir une autre offre' : 'Choisissez une prestation' }}
                </Button>
                <!-- Normal button for ALL other offers -->
                <template v-else>
                    <Button
                        v-if="cart.isExist(item)"
                        variant="secondary"
                        class="px-8 py-5 w-full"
                        :disabled="cart.mutation.mutating || cart.query.fetching"
                        :loading="cart.state.loading[item.id]"
                        @click="cart.remove(item)"
                    >
                        Retirer et choisir une autre offre
                    </Button>
                    <Button
                        v-else
                        variant="warning"
                        class="h-10 px-5 w-full"
                        :loading="cart.state.loading[item.id]"
                        :disabled="cart.mutation.mutating || cart.query.fetching"
                        @click="cart.add(item)"
                    >
                        Choisissez cette offre
                    </Button>
                </template>
            </div>
            <div v-if="!shouldOpenModal" class="mt-5">
                <h3 class="text-sm/6 font-medium text-gray-950">Caracteristiques:</h3>
                <div v-html="item.caracteristiques"></div>
            </div>
        </Card>
    </li>

    <!-- ✅ Modal - ONLY for "Conduite supevisée" and "Pass Permis à la carte" -->
    <Teleport to="body">
        <div v-if="showDetails && shouldOpenModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-3 sm:p-4 overflow-hidden">
            <div class="bg-white rounded-lg w-full max-w-md max-h-[85vh] overflow-y-auto p-4 sm:p-6 shadow-lg">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-lg sm:text-xl font-bold pr-4">{{ item.name }}</h2>
                    <button @click="closeDetails" class="text-gray-500 hover:text-gray-700 text-2xl leading-none flex-shrink-0">×</button>
                </div>
                <div class="space-y-3">
                    <div v-for="service in services" :key="service.id" v-show="service.price > 0" class="border rounded-lg p-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm truncate">{{ service.name }}</p>
                            <p class="text-xs text-gray-600">{{ moneyFormat(service.price) }} {{ service.hours ? `• ${service.hours}h` : '' }}</p>
                        </div>
                        <Button variant="primary" class="h-8 px-3 text-xs flex-shrink-0 whitespace-nowrap" :loading="cart.state.loading[item.id]" :disabled="cart.mutation.mutating || cart.query.fetching" @click="addServiceToCart(service)">
                            Ajouter
                        </Button>
                    </div>
                </div>
                <button @click="closeDetails" class="w-full mt-6 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 text-sm font-medium">
                    Fermer
                </button>
            </div>
        </div>
    </Teleport>
</template>
