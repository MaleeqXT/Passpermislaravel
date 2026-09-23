<script setup lang="ts">
import { DeleteIcon } from '@adersolutions/icons';
import { getTotalBalance, getCartTotal } from '@espace-admin/utils';
import { routes } from '@espace-admin/routes';
import { ViewIcon } from '@adersolutions/icons';
import { CartStatusEnum, CartStatus, PaymentStatusEnum } from '@common/enums';
import { dateFormat, moneyFormat } from '@shared/utils';
import { ItemsCopy } from '@common/components';
import { Card, Filters, Thumb, Badge, Page, DataTable } from '@shared/components';
import { headings } from './cart';
import { BulkActionType, DataListType } from '@shared/types';
import { CartType } from '@common/types';

type PropsType = {
    carts: DataListType<CartType[]>;
};

defineProps<PropsType>();

const bulkActions: BulkActionType[] = [
    {
        label: 'Voir détails',
        icon: ViewIcon,
        variant: 'secondary',
        isLink: true,
        onAction: (item) => route(routes.shop.cart.show, item.id),
    },
    {
        label: 'Supprimer',
        icon: DeleteIcon,
        variant: 'danger',
        confirm: {
            url: routes.shop.cart.delete,
        },
    },
];


// ✅ Total price: if the cart already has a paid sale, use its amount
const getTotalPrice = (items = []) => {
    // cart list passes entire cart object as item, not cartDetails array, so
    // defensive check elsewhere uses getTotalPrice(item.cart_details). here the
    // helper is used exclusively for that purpose in the template.
    let total = 0;
    items?.forEach((item) => {
        // If the parent cart was converted to a sale, favour the recorded value
        if (item.sale && item.sale.payment_status === PaymentStatusEnum.PAID && typeof item.sale.amount !== 'undefined') {
            total += Number(item.sale.amount);
            return;
        }

        const offer = item.offer ?? item;
        const selected = item.selected_price_type ?? offer.selected_price_type ?? 'final';
        const price =
            selected === 'second'
                ? offer.second_price ?? offer.final_price ?? 0
                : offer.final_price ?? offer.second_price ?? 0;

        total += Number(price);
    });
    return total;
};

const getCartPaidTotals = (cart: any) => {
    // if multiple sales exist
    if (cart.sales && Array.isArray(cart.sales) && cart.sales.length > 0) {
        const paidSales = cart.sales.filter(
            (s: any) => s.payment_status === PaymentStatusEnum.PAID
        );

        if (paidSales.length > 0) {
            const totalAmount = paidSales.reduce(
                (sum: number, s: any) => {
                    const amount = parseFloat(String(s.amount)) || 0;
                    return sum + amount;
                },
                0
            );

            const totalBalance = paidSales.reduce(
                (sum: number, s: any) => {
                    const balance = parseFloat(String(s.balance)) || 0;
                    return sum + balance;
                },
                0
            );

            return {
                amount: parseFloat(totalAmount.toFixed(2)),
                balance: parseFloat(totalBalance.toFixed(2)),
            };
        }
    }

    // fallback single sale
    if (
        cart.sale &&
        cart.sale.payment_status === PaymentStatusEnum.PAID
    ) {
        return {
            amount: parseFloat(String(cart.sale.amount || 0)),
            balance: parseFloat(String(cart.sale.balance || 0)),
        };
    }

    return null;
};

// ✅ Total balance: respect recorded sale balance when available
const getTotalBalanceLocal = (items = []) => {
    let total = 0;
    items?.forEach((item) => {
        if (item.sale && item.sale.payment_status === PaymentStatusEnum.PAID && typeof item.sale.balance !== 'undefined') {
            total += Number(item.sale.balance);
            return;
        }

        const offer = item.offer ?? item;
        const selected = item.selected_price_type ?? offer.selected_price_type ?? 'final';
        const bal =
            selected === 'second'
                ? offer.balance_2 ?? offer.balance ?? 0
                : offer.balance ?? offer.balance_2 ?? 0;

        total += Number(bal);
    });
    return total;
};

</script>

<template>
    <Page title="Pannier" width="xl">
        <Card block>
            <div>
                <Filters :tabs="[{ name: 'Tous', id: '' }, ...Object.values(CartStatus)]" default-tab="" key-tab="status" />

                <DataTable v-slot="{ item }" :headings="headings" :items="carts" :bulk-actions="bulkActions">
                    <td class="cell">
                        <div class="flex gap-2 items-center">
                            <Thumb :src="item.student?.user?.media || item.student?.user?.profile_photo_url" size="xs" />
                            <b>{{ item.student?.user?.name }}</b>
                        </div>
                    </td>

                    <td class="cell">
                        <ItemsCopy class="!gap-0" :email="item?.student?.user.email" :phone="item?.student?.user.phone" />
                    </td>

                    <td class="cell">{{ item.cart_details?.length }} offers</td>

                    <!-- ✅ Use sale totals if cart has been paid, otherwise compute from details -->
                    <td class="cell">
                        {{
    (() => {
        const totals = getCartPaidTotals(item);
        if (totals) {
            return totals.balance + ' H';
        }
        return getTotalBalanceLocal(item.cart_details) + ' H';
    })()
}}
                    </td>

                    <!-- ✅ Use sale amount or compute price -->
                    <td class="cell">
                        {{
    (() => {
        const totals = getCartPaidTotals(item);
        if (totals) {
            return moneyFormat(totals.amount);
        }
        return moneyFormat(getTotalPrice(item.cart_details));
    })()
}}
                    </td>

                    <td class="cell capitalize">
                        {{ dateFormat(item.created_at, 'fr') }}
                        {{ dateFormat(item.created_at, 'shortTime') }}
                    </td>

                    <td class="cell">
                        <Badge :id="item.status" :options="CartStatus" />
                    </td>
                </DataTable>
            </div>
        </Card>
    </Page>
</template>

