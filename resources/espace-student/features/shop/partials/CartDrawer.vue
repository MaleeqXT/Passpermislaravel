<script setup lang="ts">
import { ArrowLeftIcon, ChevronUpIcon, CartIcon, DeleteIcon } from '@adersolutions/icons';
import { ref } from 'vue';
import { Button, Drawer, TabSwitch } from '@shared/components';
import { moneyFormat } from '@shared/utils';
import { useCart } from '@shared/stores';
import { PaymentCardPaypal, PaymentCardStripe } from '@common/components';
import ClientButton from '@espace-client/components/ClientButton.vue';
import { routes as clientRoutes } from '@espace-client/routes';
import { routes } from '@espace-student/routes';
import { usePage } from '@inertiajs/vue3';

const show = defineModel({ type: Boolean });

const cart = useCart();
const page = usePage();

const onClose = () => {
    show.value = false;
};




// Get user's agency based on city (same logic as useCart store)
const getUserAgency = (): string => {
    const user = (page.props.auth as any)?.user;
    if (!user) return 'criel'; // guest fallback to criel

    const ville = String(user.ville ?? '').toLowerCase();

    if (ville.includes('creil')) return 'criel';
    if (ville.includes('toulouse')) return 'toulouse';

    return 'criel'; // default
};

// Helper: Get agency pricing for an offer (user's agency specific)
const getAgencyPricing = (offer: any) => {
    if (!offer?.agency_pricing) return null;
    let pricingList = typeof offer.agency_pricing === 'string'
        ? JSON.parse(offer.agency_pricing)
        : offer.agency_pricing;

    if (!Array.isArray(pricingList) || !pricingList.length) return null;

    // Get user's agency
    const userAgency = getUserAgency();

    // Find matching agency pricing, fallback to first
    return pricingList.find(
        (p: any) => String(p.agency ?? '').toLowerCase() === userAgency
    ) ?? pricingList[0];
};

// Helper: Get price for cart item (use store's itemPrices which reads overrides)
const getItemPrice = (item: any): number => {
    // Use string key to match computed map keys
    const itemKey = String(item?.id ?? '');

    // First try to get from cart store's itemPrices (which reads sessionStorage overrides)
    const storedPrice = cart.itemPrices[itemKey];
    if (storedPrice !== undefined && storedPrice !== null && Number(storedPrice) > 0) {
        return Number(storedPrice);
    }

    // Fallback: read from sessionStorage directly (accept numeric strings too)
    try {
        const overrides = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
        const cartItemId = itemKey;
        const offerId = String(item?.offer?.id || '');
        const override = (cartItemId && overrides[cartItemId]) || overrides[offerId];
        if (override && override.price !== undefined && override.price !== null) {
            const p = Number(override.price);
            if (!isNaN(p) && p > 0) return p;
        }
    } catch (e) {
        // ignore
    }

    // Final fallback to offer pricing
    if (cart.selectedInstallments[itemKey]?.priceType === 'installment' && cart.selectedInstallments[itemKey]?.installmentNo) {
        return cart.resolveOfferPrice(item.offer, cart.selectedInstallments[itemKey].installmentNo);
    }
    return getAgencyPricing(item.offer)?.original_price || cart.resolveOfferPrice(item.offer);
};



// Helper: Get service label or offer name (for modal-selected services)
const getItemDisplayName = (item: any): string => {
    const itemKey = String(item?.id ?? '');

    try {
        const overrides = JSON.parse(sessionStorage.getItem('cart_price_overrides') || '{}');
        const cartItemId = itemKey;
        const offerId = String(item?.offer?.id || '');
        const override = (cartItemId && overrides[cartItemId]) || overrides[offerId];
        if (override) {
            // support both `label` (stored by writeOverrideToSession) and `service_label` (passed in add)
            if (override.service_label) return String(override.service_label);
            if (override.label) return String(override.label);
        }
    } catch (e) {
        // ignore
    }

    return item.offer?.name || 'Unknown offer';
};

// Effective multi-payment for an item: prefer agency pricing, then offer, then item
const getEffectiveMultiPayment = (item: any): number => {
    const pricing = getAgencyPricing(item.offer);
    return Number(pricing?.multi_payment ?? item.offer?.multi_payment ?? item.multi_payment ?? 1);
};
</script>
<template>
    <Drawer :show="!!show" title="Mes Offers" @close="onClose">
        <ul role="list" class="flex flex-col divide-y text-xs px-3 bg-white border-y mb-5">

            <li
    v-for="(item, idx) in cart.data"
    :key="idx"
    class="px-3 py-2"
>
    <!-- ONE SINGLE WHITE CARD -->
    <div class="bg-white border rounded-md px-3 py-2 flex flex-col gap-0.5">

        <!-- TITLE + DELETE -->
        <div class="flex justify-between items-center">
            <h4 class="text-sm font-semibold text-gray-900 truncate">
                {{ getItemDisplayName(item) }}
            </h4>

            <Button
                :loading="cart.state.loading[item.id]"
                :disabled="cart.mutation.mutating || cart.query.fetching"
                variant="danger"
                size="xs"
                :icon="DeleteIcon"
                class="h-6 w-6 p-0"
                @click="cart.remove(item)"
            />
        </div>

        <!-- BADGE -->
        <span
            v-if="getEffectiveMultiPayment(item) > 1"
            class="text-[10px] text-orange-600 bg-orange-100 rounded-full px-2 py-[1px] w-fit"
        >
            Paiement en {{ getEffectiveMultiPayment(item) }} fois
        </span>

        <!-- PRICE -->
        <span class="text-base font-bold text-gray-900 leading-none mt-1">
            {{ moneyFormat(getItemPrice(item)) }}
        </span>

        <!-- INSTALLMENT -->
        <span
            v-if="getEffectiveMultiPayment(item) > 1"
            class="text-[10px] text-green-600 font-medium"
        >
            {{ moneyFormat(cart.resolveOfferPrice(item.offer, 1)) }}
            × {{ getEffectiveMultiPayment(item) }}
        </span>

        <!-- STUDENT INFO -->
        <div v-if="page.props.auth?.user" class="text-xs text-gray-500 mt-1">
            Étudiant: {{ page.props.auth.user.name }} (ID: {{ page.props.auth.user.id }}, Email: {{ page.props.auth.user.email }})
        </div>

    </div>
</li>



        </ul>
        <div class="flex-1"></div>

       <div class="p-4 bg-white border-t sticky bottom-0">

    <div class="flex justify-between items-center text-base">
        <span class="font-medium text-gray-600">Sous-total</span>
        <span class="font-semibold">{{ moneyFormat(cart.prices.subTotal) }}</span>
    </div>

    <p class="text-xs text-gray-500 py-3">
        {{ cart.count }} offre{{ cart.count > 1 ? 's' : '' }}
    </p>

    <div class="flex justify-between items-center pt-4 border-t">
        <span class="text-lg font-bold">Total</span>
        <span class="text-2xl font-extrabold text-gray-900">
            {{ moneyFormat(cart.prices.total) }}
        </span>
    </div>

    <p class="mt-3 text-[11px] text-gray-500 leading-snug">
     pour appliquer le paiement par tranches, vous devez confirmer les offres du panier et procéder aux offres que vous
                choisissez dans la page suivante « page de paiement »
    </p>

    <ClientButton
        :href="route(clientRoutes.checkout, { redirect_to: route(routes.shop.index) })"
        :disabled="!cart.count"
        reload
        variant="danger"
        class="w-full mt-4 text-base py-3 rounded-xl shadow-lg"
        @click="show = false"
    >
        Commander maintenant
    </ClientButton>

</div>

    </Drawer>
</template>
