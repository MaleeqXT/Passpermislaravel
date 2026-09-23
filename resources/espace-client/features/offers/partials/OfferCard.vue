<script setup lang="ts">
import { NotificationFilledIcon } from '@adersolutions/icons';
import { OfferType } from '@common/types';
import { Link, usePage } from '@inertiajs/vue3';
import { routes } from '@espace-client/routes';
import { PriceCircle } from '@espace-client/components';
import { Spinner } from '@shared/components';
import { useCart } from '@shared/stores';
import { getFilePath } from '@shared/utils';
import { computed, ref } from 'vue';

type PropsType = {
    offer: OfferType;
};
const props = defineProps<PropsType>();
const carts = useCart();

const showAlt = ref(false);


// ✅ Get agency pricing for the offer
const getAgencyPricing = (offer: any) => {
    const page = usePage();
    let pricingList = offer.agency_pricing;

    if (typeof pricingList === 'string') {
        try {
            pricingList = JSON.parse(pricingList);
        } catch (e) {
            return null;
        }
    }

    if (!Array.isArray(pricingList) || !pricingList.length) return null;

    if (!page.props.auth?.user) {
        // Guest: prefer an explicit `?agency=` query parameter when present,
        // otherwise default to toulouse if available, otherwise first.
        let queryAgency = '';
        if (typeof window !== 'undefined') {
            const params = new URLSearchParams(window.location.search);
            queryAgency = String(params.get('agency') ?? '').toLowerCase().trim();
        } else if (page.props?.url) {
            try {
                const urlObj = new URL(String(page.props.url));
                queryAgency = String(new URLSearchParams(urlObj.search).get('agency') ?? '').toLowerCase().trim();
            } catch (e) {
                queryAgency = '';
            }
        }

        if (queryAgency) {
                // Normalize agency name: creil/criel are the same, toulouse is toulouse
                let normalizedQuery = queryAgency;
                if (normalizedQuery === 'creil' || normalizedQuery === 'criel') {
                    normalizedQuery = 'criel'; // database uses 'criel'
                }

                const foundByQuery = pricingList.find((p: any) => {
                    let pAgency = String(p.agency ?? '').toLowerCase().trim();
                    // Also normalize on the data side
                    if (pAgency === 'creil' || pAgency === 'criel') {
                        pAgency = 'criel';
                    }
                    return pAgency === normalizedQuery;
                });
                if (foundByQuery) return foundByQuery;
        }

        return pricingList.find((p: any) => String(p.agency ?? '').toLowerCase().trim() === 'toulouse') ?? pricingList[0];
    }

let agency = '';
const user = (page.props.auth as any)?.user;

// ✅ Check for query parameter override
let queryAgency = '';
if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    queryAgency = String(params.get('agency') ?? '').toLowerCase().trim();
}

// ✅ ADMIN SPECIAL CASE
if (user?.id === 1 || user?.email === 'admin@pf.com') {
    if (queryAgency === 'creil' || queryAgency === 'criel') {
        agency = 'criel';
    }
    else if (queryAgency === 'toulouse') {
        agency = 'toulouse';
    }
}
// ✅ SECRETARY SPECIAL CASE - Default to Creil
else if ((page.props as any)?.auth?.user?.is_secretary) {
    if (queryAgency === 'toulouse') {
        agency = 'toulouse';
    } else {
        agency = 'criel'; // Default to Creil for secretaries
    }
}
else {
    // ✅ YOUR ORIGINAL CODE (UNCHANGED)
    const ville = String(user?.ville ?? '').toLowerCase().trim();

    if (ville.includes('creil') || ville.includes('criel')) agency = 'criel';
    else if (ville.includes('toulouse')) agency = 'toulouse';
}
    const found = pricingList.find(
        (p: any) =>
            String(p.agency ?? '').toLowerCase().trim() === agency
    );

    return found ?? pricingList[0];
};

// ✅ Get agency-specific caracteristiques based on user's agency
const getAgencyCaracteristiques = () => {
    const pricing = getAgencyPricing(props.offer);
    return pricing?.caracteristiques ?? props.offer.caracteristiques ?? '';
};

const firstValidPrice = (...values: unknown[]) => {
    for (const value of values) {
        const price = Number(value);

        if (Number.isFinite(price) && price > 0) {
            return price;
        }
    }

    return null;
};

const getOfferPrice = (pricing: any) => {
    return firstValidPrice(
        pricing?.original_price,
        pricing?.price_ht,
        pricing?.total_payment,
        pricing?.final_price,
        pricing?.discounted_price,
        props.offer.original_price,
        props.offer.price_ht,
        props.offer.final_price,
        props.offer.discounted_price,
    );
};

const firstValidBalance = (...values: unknown[]) => {
    for (const value of values) {
        const balance = Number(value);

        if (Number.isFinite(balance) && balance > 0) {
            return balance;
        }
    }

    return null;
};

const parseHoursFromText = (...values: unknown[]) => {
    for (const value of values) {
        const text = String(value ?? '')
            .replace(/<[^>]*>/g, ' ')
            .replace(/&nbsp;/g, ' ');
        const match = text.match(/(\d+(?:[.,]\d+)?)\s*h/i);

        if (match) {
            const hours = Number(match[1].replace(',', '.'));

            if (Number.isFinite(hours) && hours > 0) {
                return hours;
            }
        }
    }

    return null;
};

const getOfferBalance = (pricing: any) => {
    return firstValidBalance(
        pricing?.balance,
        props.offer.balance,
        pricing?.balance_2,
        props.offer.balance_2,
    ) ?? parseHoursFromText(
        pricing?.caracteristiques,
        props.offer.caracteristiques,
        props.offer.name,
    );
};

// ✅ Price from agency pricing, with offer-level fallback when agency pricing is empty/null
const originalPrice = computed(() => {
    const pricing = getAgencyPricing(props.offer);

    if (showAlt.value) {
        return firstValidPrice(pricing?.second_price, props.offer.second_price) ?? getOfferPrice(pricing);
    }

    return getOfferPrice(pricing);
});

// ✅ Integer price
const parsedPrice = computed(() => {
    const price = originalPrice.value;

    return price !== null && price !== undefined ? parseInt(price.toString().split('.')[0]) : null;
});

// ✅ Integer balance (agency-specific)
const currentBalance = computed(() => {
    const pricing = getAgencyPricing(props.offer);

    const balance = showAlt.value
        ? firstValidBalance(pricing?.balance_2, props.offer.balance_2) ?? getOfferBalance(pricing)
        : getOfferBalance(pricing);

    return balance !== null && balance !== undefined ? parseInt(balance.toString().split('.')[0]) : null;
});

const toggleOffer = () => {
    if (props.offer.balance_2 && props.offer.second_price) {
        showAlt.value = !showAlt.value;
    }
};

// ✅ Modal to show offer details and services
const showDetails = ref(false);
const openDetails = () => (showDetails.value = true);
const closeDetails = () => (showDetails.value = false);

// Add computed property to check if the offer is already in the cart
const isOfferInCart = computed(() => {
    return carts.isExist({ ...props.offer, selected_price_type: showAlt.value ? 'second' : 'final' });
});

// Update the modal opening logic to prevent opening if the offer is already in the cart
const shouldOpenModal = computed(() => {
    const name = String(props.offer?.name ?? '').toLowerCase();
    return !isOfferInCart.value && (name.includes('conduite') || name.includes('pass permis'));
});

// ✅ Parse services from description HTML (global regex matching)
const parseServices = (htmlDescription: string) => {
    if (!htmlDescription) return [];

    // Clean HTML entities and tags
    const cleaned = htmlDescription
        .replace(/<[^>]*>/g, ' ') // strip tags
        .replace(/&nbsp;/g, ' ')
        .replace(/&euro;/g, '€')
        .replace(/\s+/g, ' ') // normalize whitespace
        .trim();

    const services: { id: string; name: string; price: number; hours?: number }[] = [];

    // Precompute all explicit hours mentions (e.g. '10H', '2h', 'forfait 20H') with positions
    const hoursRegex = /(\d+(?:[.,]\d+)?)\s*[Hh]\b/g;
    const hoursMatches: Array<{ idx: number; value: number }> = [];
    let hm: RegExpExecArray | null;
    while ((hm = hoursRegex.exec(cleaned)) !== null) {
        const v = parseFloat(hm[1].replace(',', '.'));
        if (!isNaN(v)) hoursMatches.push({ idx: hm.index, value: v });
    }

    // Global regex to find all price occurrences with a preceding name fragment
    const globalRegex = /(.+?)[\s:=]*([0-9]+(?:[.,][0-9]+)?)\s*€/g;
    let match: RegExpExecArray | null;
    while ((match = globalRegex.exec(cleaned)) !== null) {
        let name = match[1].trim().replace(/^->\s*/, '').replace(/^\d+\.\s*/, '');
        const priceStr = match[2].replace(',', '.');
        const price = parseFloat(priceStr);

        if (!name || name.length < 2 || !price || price <= 0) continue;

        // Find nearest hours mention to this price (consider only hours that appear BEFORE the € symbol)
        // Compute numeric position of the matched price within the cleaned string
        const numericPart = match[2];
        const priceIdx = match.index + (match[0].indexOf(numericPart) >= 0 ? match[0].indexOf(numericPart) : 0);
        const matchEnd = match.index + match[0].length; // position right after the € symbol
        let nearest: { idx: number; value: number } | null = null;
        let bestDist = Infinity;
        // Only consider hour tokens that occur BEFORE the € (i.e., before matchEnd)
        const candidateHours = hoursMatches.filter(h => h.idx < matchEnd);
        for (const h of candidateHours) {
            const d = Math.abs(h.idx - priceIdx);
            if (d < bestDist && d <= 40) { // strict proximity: only very close hours
                bestDist = d;
                nearest = h;
            }
        }

        const hours = nearest ? Number(nearest.value) : undefined;

        // Avoid duplicates with same name+price+hours
        const exists = services.some(s => s.name === name && s.price === price && s.hours === hours);
        if (exists) continue;

        const id = `${props.offer.id}_srv_${services.length}`;
        services.push({ id, name, price, hours });
    }

    return services;
};

// ✅ Get parsed services from offer description
const services = computed(() => {
    const caracteristiques = getAgencyCaracteristiques();
    return parseServices(caracteristiques);
});


</script>

<template>
    <li
        :class="['bg-white box p-5 overflow-hidden flex flex-col', shouldOpenModal ? 'cursor-pointer' : '']"
        :style="{ '--customcolor': offer.color }"
        @click="shouldOpenModal ? openDetails() : null"
    >
        <div class="clippath z-0 opacity-30"></div>
        <div class="relative flex flex-col z-1 flex-1">
            <img :src="getFilePath(offer, true)" :alt="offer.imageAlt" class="h-40 w-auto object-contain max-w-60 mx-auto" />

            <!-- ✅ Show both balances (main or alt) -->
            <PriceCircle v-if="parsedPrice !== null" class="text-custom mx-auto">
                <span
                    v-if="currentBalance !== null"
                    class="text-[26px] text-gray-600 text-center block -mt-4 -mb-1 font-normal tracking-normal"
                >
                    {{ currentBalance }}h
                </span>
                <b class="flex items-baseline -mr-2"> {{ parsedPrice }}€ </b>
            </PriceCircle>

            <div class="relative my-10">
                <div class="prose prose-sm" v-html="getAgencyCaracteristiques()"></div>
            </div>

            <Link
                v-if="offer.multi_payment > 1"
                :href="route(routes.contact.index)"
                class="text-sm text-dark bg-slate-200 bg-rainbow rounded-lg p-2 flex gap-2 items-center mb-4"
            >
                <NotificationFilledIcon class="w-9 h-9 p-2 rounded-lg bg-dark/10 text-custom" />
                <div class="mb-px ml-1 text-2xs">
                    Pour les paiements en
                    <span v-for="(i, index) in offer.multi_payment">
                        <span v-if="offer.multi_payment === i && offer.multi_payment > 2"> ou </span>
                        <span v-else-if="offer.multi_payment > 2 && i > 2"> , </span>
                        <template v-if="i !== 1">
                            {{ i }}
                        </template>
                    </span>
                    fois, être recontacté par le secrétariat
                    <b class="text-2xs block underline cursor-pointer">Clique ici</b>
                </div>
            </Link>

            <!-- Services are shown inside modal (open the card to view details) -->
        </div>

     <button
    :class="[
        'text-white border-b-4 font-semibold h-12 w-full px-5 rounded-lg btn-m flex-center relative mb-5',
        carts.state.loading[offer.id] ||
        carts.isExist({ ...offer, selected_price_type: showAlt ? 'second' : 'final' })
            ? 'bg-gray-500 border-gray-500 cursor-not-allowed'
            : 'border-dark2 bg-custom hover:bg-dark2',
    ]"
    @click.stop="
        carts.isExist({ ...offer, selected_price_type: showAlt ? 'second' : 'final' })
            ? () => {}
            : carts.add(offer, () => {}, showAlt ? 'second' : 'final')
    "
>
    <span v-if="carts.state.loading[offer.id]" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
        <Spinner class="text-white w-5 h-5 animate-spin" />
    </span>
    <span :class="[carts.state.loading[offer.id] ? 'opacity-0' : 'opacity-100']">
        {{
            carts.isExist({ ...offer, selected_price_type: showAlt ? 'second' : 'final' })
                ? 'Déjà dans le panier'
                : 'Choisir cette offre'
        }}
    </span>
</button>

        <span class="absolute bg-slate-100 w-64 h-64 rounded-full -right-28 -top-36 z-0"></span>
        <span class="absolute bg-custom w-52 h-52 rounded-full -left-28 -bottom-36 z-0 opacity-70"></span>
    </li>

    <!-- Modal: offer details and services -->
    <div v-if="showDetails" class="fixed inset-0 z-50 flex items-center justify-center px-3 sm:px-4" @click.self="closeDetails">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative bg-white rounded-lg shadow-lg w-full max-w-2xl md:max-w-3xl z-10 p-4 sm:p-6 overflow-auto max-h-[90vh]">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-base sm:text-lg font-semibold pr-4">{{ offer.name ?? 'Détails de l\'offre' }}</h3>
                <button @click="closeDetails" class="text-gray-500 hover:text-gray-800 flex-shrink-0">✕</button>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 sm:gap-6">
                <img :src="getFilePath(offer, true)" :alt="offer.imageAlt" class="w-24 h-24 sm:w-36 sm:h-36 object-contain flex-shrink-0" />
                <div class="flex-1 min-w-0">

                    <div v-if="services.length > 0" class="space-y-2 sm:space-y-3">
                        <div v-for="service in services" :key="service.id" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-3 border rounded-lg">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate">{{ service.name }}</div>
                                <div class="text-sm font-bold text-custom">{{ service.price }}€</div>
                            </div>
                            <div class="flex-shrink-0">
                                <button
                                    @click.stop="carts.add({ ...offer, final_price: service.price, override_price: true, balance: service.hours ?? 0, caracteristiques: service.name, multi_payment: 1, service_label: service.name }, () => { closeDetails(); })"
                                    :class="[
                                        'w-full sm:w-auto text-white text-xs sm:text-sm font-semibold px-3 py-2 rounded-lg transition whitespace-nowrap',
                                        carts.state.loading[offer.id]
                                            ? 'bg-gray-400 cursor-not-allowed'
                                            : 'bg-custom hover:bg-dark2 border-b-2 border-dark2'
                                    ]"
                                >
                                    <span v-if="!carts.state.loading[offer.id]">Ajouter</span>
                                    <span v-else class="inline-flex"><Spinner class="w-4 h-4 animate-spin" /></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
