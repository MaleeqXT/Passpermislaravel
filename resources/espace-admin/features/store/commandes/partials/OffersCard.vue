<script setup lang="ts">
import { Button, Thumb } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { ref, computed } from 'vue';
import { ReceiptRefundIcon } from '@adersolutions/icons';
import { PaymentTypeEnum, PaymentStatusEnum } from '@common/enums';
import { useAlert } from '@shared/stores';
import { getFilePath, moneyFormat } from '@shared/utils';
import { Link } from '@inertiajs/vue3';
import { SizeEnum } from '@shared/enums';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    items: {
        type: Object,
        default: () => ({}),
    },
    isOrder: {
        type: Boolean,
    },
    paymentInfo: {
        type: Object,
        method: String,
        status: String,
    },
    orderId: String,
    // when there are multiple sales (one per offer), pass the sales array
    // the component will match each offer to its corresponding sale
    sales: {
        type: Array,
        default: () => [],
    },
    // keep 'sale' for backwards compatibility - single sale object
    sale: Object,
    // when viewing commandes, pass the current offer index to show only that offer
    offerIndex: {
        type: Number,
        default: null,
    },
});
const inProgress = ref(false);
const alert = useAlert();
const page = usePage();

// Find the sale that corresponds to a specific offer/item
// When multiple offers are paid together, each has its own sale row
// Match by offer ID (reliable way like student?.user?.name)
const getSaleForItem = (item: any, index: number) => {
    // if no sales array provided, fall back to single sale prop
    if (!props.sales || props.sales.length === 0) {
        return props.sale;
    }

    // Match by offer ID when available - this works correctly when
    // multiple offers are loaded (Toulouse, Creil, etc) regardless of
    // items/sales array order
    const itemOfferId = getOfferId(item);
    if (itemOfferId && Array.isArray(props.sales)) {
        const foundBySale = props.sales.find((sale: any) =>
            Array.isArray(sale?.cart_details) &&
            sale.cart_details.some((cd: any) => cd.offer_id === itemOfferId)
        );
        if (foundBySale) {
            return foundBySale;
        }
    }

    // Fallback to index when ID matching fails
    let candidate = props.sales[index] ?? props.sale;

    // only try to resolve by sale.id when we're rendering an "order"
    // (commande) view; in the cart page props.sale refers to the overall
    // cart sale, so matching by id would wrongly collapse to the first
    // sale for every item.
    if (props.isOrder && props.sale && candidate?.id !== props.sale.id) {
        const found = props.sales.find((s: any) => s.id === props.sale.id);
        if (found) {
            candidate = found;
        }
    }

    return candidate;
};

// helper to safely access the offer object for a cart detail or when
// the detail is an offer itself
const getOffer = (item: any) => {
    return item?.offer ?? item ?? null;
};

const getOfferName = (item: any) => {
    const offer = getOffer(item) ?? {};
    return (
        offer.name ||
        offer.title ||
        offer.label ||
        offer.offer_name ||
        offer.display_name ||
        (offer.id ? `Offer ${offer.id}` : String(item.offer_id ?? item.id ?? ''))
    );
};

const getOfferId = (item: any) => {
    const offer = getOffer(item) ?? {};
    return offer.id ?? item.offer_id ?? null;
};


// sometimes the caller will provide an object with `data` property (cart
// details) but in other flows the array may be on the sale itself – normalize
// to a consistent array so the template can just loop over `list.value`.
// Check if current route is for commandes
const isCommandesRoute = computed(() => {
    const url = window.location.pathname;
    return url.includes('/commandes/') && !url.includes('/cart');
});

const list = computed(() => {
    let items: any[] = [];

    if (Array.isArray(props.items)) {
        items = props.items;
    } else if (Array.isArray(props.items?.data)) {
        items = props.items.data;
    } else if (Array.isArray(props.items?.cart_details)) {
        items = props.items.cart_details;
    }

    // if the caller specified an offerIndex, show only that specific
    // item; this makes the component reusable for both commandes (where we
    // automatically pass the index) and cart pages (where we manually pass
    // it as well).  The route check is no longer needed.
    if (props.offerIndex !== null) {
        return [items[props.offerIndex]].filter(Boolean);
    }

    // fallback to all items when no index is given
    return items;
});

const headings = [
    { name: 'Produit', colspan: '2' },
    { name: 'Prix', className: 'text-center font-normal px-3' },
    { name: 'Qtn', className: 'text-center font-normal px-3' },
    { name: 'Heures', className: 'text-center font-normal px-3' },
    { name: 'Tranches', className: 'text-center font-normal px-3' },
    { name: (props.sales?.some((s: any) => s.payment_status === PaymentStatusEnum.PAID) || props.sale?.payment_status === PaymentStatusEnum.PAID) ? 'Montant payé' : 'Total Payé', className: 'text-center font-normal px-3' },
    {
        name: props.isOrder && props.paymentInfo?.method === PaymentTypeEnum.STRIPE ? 'Action' : null,
        className:
            props.isOrder && props.paymentInfo?.method === PaymentTypeEnum.STRIPE ? 'text-center pr-4 sticky right-0 bg-slate-100' : null,
    },
];

// ---- agency pricing helpers -------------------------------------------------

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
    if (!Array.isArray(pricingList) || !pricingList.length) return null;

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

// return sale-level balance when viewing a completed sale, otherwise use
// the usual per‑offer calculation
const getItemBalance = (item: any, index: number) => {
    const sale = getSaleForItem(item, index);
    return sale?.balance ?? 0;
};

const getItemPaidAmount = (item: any, index: number) => {
    const sale = getSaleForItem(item, index);
    return sale?.amount ?? 0;
};

const submit = async (id) => {
    inProgress.value = true;
    const data = {
        offer_id: id,
    };
    await axios
        .post(route(routes.shop.commandes.refund, props.orderId), data)
        .then(() => {
            alert.show({ type: 'success', title: 'Refunded' });
        })
        .catch(() => {
            alert.show({ type: 'error', title: 'an error happend' });
        });
    inProgress.value = false;
};
const isRefundAvailable = () => {
    return !(props.paymentInfo.status !== PaymentStatusEnum.PAID || props.paymentInfo.method !== PaymentTypeEnum.STRIPE);
};</script>

<template>
    <section class="my-5">
        <h3 class="font-bold mb-2">Offres commandé</h3>

        <ul class="text-sm space-y-2">
            <li  v-for="(item, index) in list" :key="item.id" class="flex gap-10 bg-white box">
                <div class="flex-1 flex gap-2 h-[106px]">
                    <img
                        class="object-cover aspect-square h-full rounded-l-xl"
                        :src="getFilePath(item.offer?.media, true)"
                        @error="(e) => ((e.target as HTMLImageElement).src = '/assets/images/placeholder.png')"
                    />
                    <div class="flex-1 py-2">
                        <template v-if="getOfferId(item)">
                            <Link class="btn btn-link btn-dark !pl-0" :href="route(routes.shop.offers.edit, getOfferId(item))">
                                {{ getOfferName(item) }}
                            </Link>
                        </template>
                        <template v-else>
                            <div class="font-semibold">{{ getOfferName(item) }}</div>
                        </template>
                        <p class="mb-3 text-xs opacity-70">
                            {{ getOffer(item)?.description || getOffer(item)?.short_description || '' }}
                        </p>
                        <Button v-if="isOrder" variant="danger" class="h-6" :disabled="!isRefundAvailable()" @click="submit(item.offer.id)">
                            {{ isRefundAvailable() ? 'Rembourser' : '' }}
                        </Button>
                    </div>
                </div>

        <dl class="w-1/3 divide-y divide-gray-200 text-xs py-1 px-3">
<!-- ✅ Balance -->
<div class="flex items-center justify-between py-1">
    <dt class="text-gray-600">
        {{ (getSaleForItem(item)?.payment_status === PaymentStatusEnum.PAID) ? 'Balance restante' : 'Balance' }}
    </dt>
    <dd class="font-medium text-gray-900">
{{ getItemBalance(item, index) }}
    </dd>
</div>


    <!-- ✅ Tranches -->
    <div class="flex items-center justify-between py-1">
        <dt class="text-gray-600">Tranches</dt>
        <dd class="font-medium text-gray-900">{{ item.tranches }}</dd>
    </div>

    <!-- ✅ Prix de base (original price) -->
    <div class="flex items-center justify-between py-1">
        <dt class="text-gray-600">Prix de base</dt>
        <dd class="font-medium text-gray-900">
            {{ moneyFormat(getItemPaidAmount(item,index)) }}
        </dd>
    </div>

    <!-- ✅ Montant payé (actual final or second) -->
    <div class="flex items-center justify-between py-1">
        <dt class="text-gray-600">
            {{ props.sale && props.sale.payment_status === PaymentStatusEnum.PAID ? 'Montant payé (total)' : 'Montant payé' }}
        </dt>
        <dd class="font-medium text-gray-900">
            {{ moneyFormat(getItemPaidAmount(item,index)) }}
        </dd>
    </div>
</dl>



            </li>
        </ul>
    </section>
</template>
