<template>
    <div class="bg-dark rounded-xl overflow-hidden bg-rainbow rainbow-opacity-30">
        <div class="mx-auto max-w-7xl">
            <div class="grid grid-cols-1 gap-px bg-white/5 sm:grid-cols-2">
                <div v-for="stat in stats" :key="stat.name" class="bg-dark px-4 py-3 sm:px-6 lg:px-8">
                    <p class="text-sm font-medium leading-6 text-gray-400">{{ stat.name }}</p>
                    <p class="mt-2 flex items-baseline gap-x-2">
                        <span class="font-semibold tracking-tight text-white" :class="stat.name === 'DATE' ? 'text-2xl' : 'text-4xl'">{{
                            stat.value
                        }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { dateFormat } from '@shared/utils';
import { getTotalBalance } from '@espace-admin/utils';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { PaymentStatusEnum } from '@common/enums';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
    // when there are multiple sales (one per offer), pass the sales array
    sales: {
        type: Array,
        default: () => [],
    },
});


const formatNumber = (value: any, decimals = 2) => {
    const num = Number(value ?? 0);
    return num.toFixed(decimals);
};

// ---- helpers ----------------------------------------------------------------

/**
 * Try to parse and select one entry from an `agency_pricing` array that may
 * exist on the offer.  Logic is copied from other parts of the app (e.g.
 * client store) so the behaviour is consistent with how agencies are chosen.
 */
const getAgencyPricing = (offer: any) => {
    const page = usePage();
    let pricingList = offer?.agency_pricing;

    if (typeof pricingList === 'string') {
        try {
            pricingList = JSON.parse(pricingList);
        } catch (e) {
            pricingList = [];
        }
    }

    if (!Array.isArray(pricingList) || !pricingList.length) {
        return null;
    }

    // guest users have no `ville` so just return the first entry
    if (!page.props.auth?.user) {
        return pricingList[0];
    }

    let agency = '';
    const ville = String(page.props.auth.user.ville ?? '').toLowerCase();
    if (ville.includes('creil') || ville.includes('criel')) agency = 'criel';
    else if (ville.includes('toulouse')) agency = 'toulouse';

    return (
        pricingList.find(
            (p: any) => String(p.agency ?? '').toLowerCase() === agency
        ) ?? pricingList[0]
    );
};

/**
 * Return the numeric price that should be used for an individual cart item.
 * It honours the selected_price_type (final/second) and merges in any
 * agency_pricing values when available.
 */
const getItemPrice = (item: any) => {
    const offer = item.offer ?? item;
    const selected =
        item.selected_price_type ?? offer.selected_price_type ?? 'final';
    const pricing = getAgencyPricing(offer);

    if (pricing) {
        if (selected === 'second') {
            return Number(pricing.second_price ?? pricing.final_price ?? 0);
        }
        return Number(pricing.final_price ?? pricing.second_price ?? 0);
    }

    return selected === 'second'
        ? Number(offer.second_price ?? offer.final_price ?? 0)
        : Number(offer.final_price ?? offer.second_price ?? 0);
};

/**
 * Like `getItemPrice` but returns the balance hours for the item.
 */
const getItemBalance = (item: any) => {
    const offer = item.offer ?? item;
    const selected =
        item.selected_price_type ?? offer.selected_price_type ?? 'final';
    const pricing = getAgencyPricing(offer);

    if (pricing) {
        if (selected === 'second') {
            return Number(pricing.balance_2 ?? pricing.balance ?? 0);
        }
        return Number(pricing.balance ?? pricing.balance_2 ?? 0);
    }

    return selected === 'second'
        ? Number(offer.balance_2 ?? offer.balance ?? 0)
        : Number(offer.balance ?? offer.balance_2 ?? 0);
};

// build a normalized list of cart details; some API responses (sales in
// certain flows) may push the details directly on the object under a different
// key so we fallback accordingly
const cartItems = () => {
    return (
        props.data?.cart_details ||
        props.data?.cart?.cart_details ||
        props.data?.sale?.cart_details ||
        []
    );
};

// ✅ Total price: respect second_price if selected, take agency pricing into
// account when available
const getTotalPrice = (items = cartItems()) => {
    let total = 0;
    items?.forEach((item) => {
        total += getItemPrice(item);
    });
    return total;
};

// ✅ Total balance: respect balance_2 if second selected, check agency data
const getTotalBalanceLocal = (items = cartItems()) => {
    let total = 0;
    items?.forEach((item) => {
        total += getItemBalance(item);
    });
    return total;
};

const stats = computed(() => {
    // if the data looks like a cart (it has `cart_details`), we should
    // always compute totals from the items – ignore any nested `sale` object
    // because the sale may represent only one portion of the combined cart.
    if (props.data?.cart_details) {
        return [
            { name: 'Montant', value: getTotalPrice(cartItems()) + ' €' },
            { name: 'Balance', value: getTotalBalanceLocal(cartItems()) + 'H' },
        ];
    }

    // first, if the provided `data` object *is itself* a sale (or contains
    // one) and it has payment information, prefer that over any sales array.
    const saleObj = props.data?.payment_status ? props.data : props.data?.sale;
    if (
        saleObj?.payment_status === PaymentStatusEnum.PAID &&
        typeof saleObj.amount !== 'undefined'
    ) {
        return [
            { name: 'Montant payé', value: saleObj.amount + ' €' },
            { name: 'Balance restante', value: saleObj.balance + 'H' },
        ];
    }

    // otherwise if we were explicitly given multiple sales (for example when
    // a cart converted to several orders) sum them together.
    if (props.sales && Array.isArray(props.sales) && props.sales.length > 0) {
        const paidSales = props.sales.filter(
            (s: any) => s.payment_status === PaymentStatusEnum.PAID
        );
        if (paidSales.length > 0) {
            const totalAmount = paidSales.reduce((sum: number, s: any) => {
                const amount = parseFloat(String(s.amount)) || 0;
                return sum + amount;
            }, 0);
            const totalBalance = paidSales.reduce((sum: number, s: any) => {
                const balance = parseFloat(String(s.balance)) || 0;
                return sum + balance;
            }, 0);
            return [
                { name: 'Montant payé', value: formatNumber(parseFloat(totalAmount.toFixed(2)), 2) + ' €' },
                { name: 'Balance restante', value: formatNumber(parseFloat(totalBalance.toFixed(2)), 0) + ' H' },
            ];
        }
    }

    // fallback: derive totals from underlying items (should rarely get here)
    return [
        { name: 'Montant', value: getTotalPrice(cartItems()) + ' €' },
        { name: 'Balance', value: getTotalBalanceLocal(cartItems()) + 'H' },
    ];
});
</script>
