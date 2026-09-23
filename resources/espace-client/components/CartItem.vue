<script setup lang="ts">
import { DeleteIcon } from '@adersolutions/icons';
import { Button } from '@shared/components';
import { getFilePath, moneyFormat } from '@shared/utils';
import { useCart } from '@shared/stores';
import { computed } from 'vue';
import type { OfferType } from '@common/types';
import { usePage } from '@inertiajs/vue3';

type PropsType = {
    item: OfferType | any; // cart detail
};
const props = defineProps<PropsType>();
const cart = useCart();
const page = usePage();

const offer = computed(() => props.item.offer || props.item);

// 🔹 Pick agency pricing based on user ville or query param
const agencyPricing = computed(() => {
    if (!offer.value?.agency_pricing) return null;

    let pricingList = offer.value.agency_pricing;
    if (typeof pricingList === 'string') {
        try {
            pricingList = JSON.parse(pricingList);
        } catch {
            return null;
        }
    }

    if (!Array.isArray(pricingList) || !pricingList.length) return null;

    if (!page.props.auth?.user) {
        // Guest: prefer an explicit `?agency=` query parameter when present,
        // otherwise default to first.
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
                // Normalize agency name: creil/criel are the same
                let normalizedQuery = queryAgency;
                if (normalizedQuery === 'creil' || normalizedQuery === 'criel') {
                    normalizedQuery = 'criel';
                }

                const foundByQuery = pricingList.find((p: any) => {
                    let pAgency = String(p.agency ?? '').toLowerCase().trim();
                    if (pAgency === 'creil' || pAgency === 'criel') {
                        pAgency = 'criel';
                    }
                    return pAgency === normalizedQuery;
                });
                if (foundByQuery) return foundByQuery;
        }

        return pricingList[0];
    }

    const ville = String(page.props.auth.user.ville ?? '').toLowerCase();
    let agency = '';
    if (ville.includes('creil')) agency = 'criel';
    if (ville.includes('toulouse')) agency = 'toulouse';

    return pricingList.find(p => String(p.agency ?? '').toLowerCase() === agency) ?? pricingList[0];
});

// 🔹 Resolve price based on agency pricing + selected type + override
const selectedPrice = computed(() => {
    if (!offer.value) return 0;

    // ✅ First try to use cart store's itemPrices (which reads sessionStorage overrides correctly)
    const storedPrice = cart.itemPrices[String(props.item?.id || '')];
    if (storedPrice && storedPrice > 0) {
        return storedPrice;
    }

    // ✅ Fallback: Check if this offer has a service price override stored in sessionStorage
    const overrides = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
    const cartItemId = String((props.item && (props.item.id ?? props.item.id)) || '');
    const offerId = String(offer.value.id);
    // Prefer override keyed by cart item id, then fallback to offer id
    if (cartItemId && overrides[cartItemId]) return Number(overrides[cartItemId].price);
    if (overrides[offerId]) {
        return Number(overrides[offerId].price);
    }

    // If this cart row contains an overridden price (service added from description), use it
    if ((props.item as any).override_price && offer.value.final_price) {
        return Number(offer.value.final_price || 0);
    }

    const pricing = agencyPricing.value;

    if (props.item.selected_price_type === 'second') {
        return Number(pricing?.second_price ?? offer.value.second_price ?? 0);
    }

    return Number(pricing?.original_price ?? pricing?.price_ht ?? offer.value.final_price ?? 0);
});

// 🔹 Resolve balance based on agency pricing + selected type
const selectedBalance = computed(() => {
    if (!offer.value) return 0;

    try {
        const overrides = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
        const cartItemId = String((props.item && (props.item.id ?? props.item.id)) || '');
        const offerId = String(offer.value.id);
        const override = (cartItemId && overrides[cartItemId]) || overrides[offerId];
        if (override && typeof override.hours === 'number') {
            return Number(override.hours);
        }
    } catch {
        // ignore
    }

    const pricing = agencyPricing.value;

    if (props.item.selected_price_type === 'second') {
        return Number(pricing?.balance_2 ?? offer.value.balance_2 ?? 0);
    }

    return Number(pricing?.balance ?? offer.value.balance ?? 0);
});

const firstInstallmentPrice = computed(() => {
    if (!offer.value) return 0;

    const pricing = agencyPricing.value;

    if (!pricing?.installments?.length) return 0;

    const first = pricing.installments.find(
        (i: any) => Number(i.no) === 1
    );

    return Number(first?.amount ?? 0);
});

// ✅ Get service label override from sessionStorage
const serviceLabel = computed(() => {
    try {
        const overrides = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
        const cartItemId = String((props.item && (props.item.id ?? props.item.id)) || '');
        const offerId = String(offer.value?.id);
        return (cartItemId && overrides[cartItemId]?.label) || overrides[offerId]?.label || offer.value?.name;
    } catch {
        return offer.value?.name;
    }
});

</script>

<template>
    <li class="bg-white rounded-lg">
        <p v-if="offer.multi_payment > 1" class="text-white ring-1 bg-primary rounded-t-lg px-2 text-xs w-full">
            possible de paiement en {{ offer.multi_payment }}
        </p>

        <div class="flex items-center gap-2 p-1">
            <img :src="getFilePath(offer, true)" class="h-12 w-12 object-contain bg-gray-200 shadow-sm rounded-lg" alt="" />

            <div class="flex-1 flex flex-col gap-2">
                <h4 class="text-lg text-dark2">
                    {{ serviceLabel }}
                </h4>

                <!-- ✅ Show agency-aware price + balance -->
                <p>
                    <span class="text-lg font-bold">{{ moneyFormat(selectedPrice) }}</span>
                    <span class="text-xs font-semibold text-gray-500 ml-2">{{ selectedBalance }}h</span>

                    <span
        v-if="offer.multi_payment > 1 && firstInstallmentPrice"
        class="text-xs font-bold text-primary mb-px ml-1 opacity-80"
    >
        ({{ moneyFormat(firstInstallmentPrice) }}) {{ offer.multi_payment }} fois
    </span>
                </p>
            </div>

            <Button
                :loading="cart.state.loading[item.id]"
                :disabled="cart.mutation.mutating || cart.query.fetching"
                variant="danger"
                :icon="DeleteIcon"
                @click="cart.remove(item)"
            />
        </div>
    </li>
</template>
