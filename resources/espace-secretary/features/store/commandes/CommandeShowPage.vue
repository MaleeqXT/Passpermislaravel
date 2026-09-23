<script setup lang="ts">
import { Alerts, Back, Badge, Card, Page } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { ref, computed } from 'vue';
import { OffersCard, StatsSection, CustomerCard } from './partials';
import { CommandeType } from '@common/types';
import { PaymentStatus, PaymentType } from '@common/enums';
// sale object may include extra fields not declared in CommandeType
// (e.g. `sales` when controller passes related sales, or `offer_id` used for matching)
type PropsType = {
    sale: CommandeType & { sales?: any[]; offer_id?: string; offer?: any };
    sales?: any[];
};
const props = defineProps<PropsType>();
// `route` is a global helper; TypeScript doesn't know its signature so cast once
const routeFn = (route as any);

// If the controller provided a list of related sales, use that; otherwise
// fall back to looking at the sale object itself (for backwards compatibility)
const salesArray = computed(() => {
    if (Array.isArray(props.sales) && props.sales.length > 0) {
        return props.sales;
    }
    if (props.sale?.sales && Array.isArray(props.sale.sales)) {
        return props.sale.sales;
    }
    return props.sale ? [props.sale] : [];
});

// determine which cart detail(s) belong to the current sale
const detailsForSale = computed(() => {
    const details = props.sale?.cart?.cart_details || [];
    if (salesArray.value.length > 1) {
        // try to match current sale to the cart detail by offer_id (more reliable than index)
        const currentSale = props.sale;
        const offerId = currentSale?.offer_id ?? currentSale?.offer?.id;
        if (offerId) {
            const match = details.find((d: any) => d.offer_id === offerId || d.offer?.id === offerId);
            if (match) {
                return [match];
            }
        }
        // fallback to index if nothing found
        const idx = salesArray.value.findIndex((s: any) => s.id === props.sale.id);
        if (idx !== -1) {
            return details[idx] ? [details[idx]] : [];
        }
    }
    return details;
});

// const confirmation = ref(null);
const deleting = ref(false);

const onConfirmedDelete = () => {
    deleting.value = true;
};
</script>

<template>
    <Page width="full" padding="none">
        <div class="form-page">
            <!--  -->
            <article class="left-form">
                <Back
                    :badges="[PaymentStatus[sale.payment_status]]"
                    :title="`#${sale.reference}`"
                    :back="routeFn(routes.shop.commandes.index)"
                />
                <div class="form-content">
                    <Alerts />
                    <!-- show stats for this single sale only -->
                    <StatsSection :data="sale" />
                    <div class="rainbow"></div>
                    <OffersCard
                        v-for="item in detailsForSale"
                        :key="item.id"
                        :items="{ data: [item] }"
                        :order-id="sale.id"
                        :payment-info="{ method: sale.payment_method, status: sale.payment_status }"
                        :sale="sale"
                        :sales="salesArray"
                                                is-order
                    />
                </div>
                <!-- <ButtonGroup vertical class="form-actions" :actions="actions" /> -->
            </article>
            <article class="right-form text-sm">
                <div>
                    <h3 class="font-bold">Paiements</h3>
                    <ul class="flex flex-col gap-3 bg-gray-100 rounded-lg p-3 !mt-2">
                        <li>
                            <p class="opacity-50">N° de commande</p>
                            <span class="">{{ sale.reference }}</span>
                        </li>
                        <li>
                            <p class="opacity-50">Paiement ID</p>
                            <span class="">{{ sale.payment_id }}</span>
                        </li>

                        <li>
                            <p class="opacity-50">Payment Method</p>
                            <span class="font-semibold block mt-1">{{ PaymentType[sale.payment_method].name }}</span>
                        </li>
                        <li>
                            <p class="opacity-50">Payment Status</p>
                            <Badge :id="sale.payment_status" :options="PaymentStatus" />
                        </li>
                        <li v-if="typeof sale.amount !== 'undefined'">
                            <p class="opacity-50">Montant payé</p>
                            <span class="font-semibold">{{ sale.amount }} €</span>
                        </li>
                        <li v-if="typeof sale.balance !== 'undefined'">
                            <p class="opacity-50">Balance restante</p>
                            <span class="font-semibold">{{ sale.balance }} H</span>
                        </li>
                    </ul>
                    <CustomerCard :student="sale?.student" />
                </div>
                <!-- <ButtonGroup vertical class="form-actions" :actions="actions" /> -->
            </article>
        </div>
        <!--
        <DialogConfirm :loading="deleting" :show="!!confirmation" @close="confirmation = null" @confirm="onConfirmedDelete">
            Etes-vous sûr que vous voulez supprimer la commande
            <b class="text-red-500">{{ confirmation?.title }}</b>
            ?
        </DialogConfirm> -->
    </Page>
</template>
