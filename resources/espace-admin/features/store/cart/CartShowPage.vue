<script setup lang="ts">
import { Back, Badge, Page } from '@shared/components';
import { DialogConfirm } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { ref, computed } from 'vue';
import FeedsAndStatusCart from './FeedsAndStatusCart.vue';
import { CustomerCard, OffersCard, StatSection } from '../commandes/partials';
import { CartType } from '@common/types';
import { CartStatus, PaymentStatus } from '@common/enums';

type PropsType = {
    cart: CartType;
    // multiple sales when multiple offers paid together
    sales?: any[];
    // controls whether the sale summary block is rendered (default true)
    showSaleInfo?: boolean;
};
const props = defineProps<PropsType>();

// when the cart has been converted into multiple sales (one per offer)
// the controller passes them via props.sales; store it as an array for easy access
const salesArray = computed(() => props.sales || []);

// simply expose the cart details; each card will receive its own sale separately
const detailsForSale = computed(() => props.cart?.cart_details || []);

// helper used in the template to pick the correct sale for a given detail index
const saleForIndex = (index: number) => salesArray.value[index] ?? props.cart.sale ?? null;

const confirmation = ref(null);
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
                <Back :title="`${cart.student?.user?.name}`" :back="route(routes.shop.cart.index)" />
                <div class="form-content">
                    <Alerts />
                    <!-- Show totals computed from related sales when on cart URL -->
                    <StatSection :data="cart" :sales="salesArray" />
                    <div class="rainbow"></div>
                    <OffersCard
                            v-for="(item, index) in detailsForSale"
                            :key="item.id"
                            :items="{ data: [item] }"
                            :sale="saleForIndex(index)"
                            :sales="salesArray.length > 1 ? [saleForIndex(index)] : []"
                            :offer-index="0"
                    />
                </div>
                <!-- <ButtonGroup vertical class="form-actions" :actions="actions" /> -->
            </article>
            <article class="right-form text-sm">
                <div>
                    <h3 class="font-bold">Paiements</h3>
                    <ul class="flex flex-col gap-3 bg-gray-100 rounded-lg p-3 !mt-2">
                        <li>
                            <p class="opacity-50">ID de panier</p>
                            <span class="">{{ cart.id }}</span>
                        </li>
                        <li>
                            <p class="opacity-50">Pannier Statut</p>
                            <Badge :id="cart.status" :options="CartStatus" />
                        </li>

                        <!-- sale information (shown when cart has been converted)
                             visibility controlled by prop -->
                        <template v-if="cart.sale && (props.showSaleInfo ?? true)">
                            <li>
                                <p class="opacity-50">N° de commande</p>
                                <span>{{ cart.sale.reference }}</span>
                            </li>
                            <li v-if="cart.sale.payment_id">
                                <p class="opacity-50">Payment ID</p>
                                <span>{{ cart.sale.payment_id }}</span>
                            </li>
                            <li v-if="cart.sale.payment_status">
                                <p class="opacity-50">Payment Status</p>
                                <Badge :id="cart.sale.payment_status" :options="PaymentStatus" />
                            </li>
                            <li v-if="typeof cart.sale.amount !== 'undefined'">
                                <p class="opacity-50">Montant payé</p>
                                <span class="font-semibold">{{ cart.sale.amount }} €</span>
                            </li>
                            <li v-if="typeof cart.sale.balance !== 'undefined'">
                                <p class="opacity-50">Balance restante</p>
                                <span class="font-semibold">{{ cart.sale.balance }} H</span>
                            </li>
                        </template>
                    </ul>
                    <CustomerCard :student="cart.student" />
                </div>
                <!-- <ButtonGroup vertical class="form-actions" :actions="actions" /> -->
            </article>
        </div>
        <!-- <StatsSection :data="cart" />
        <section class="grid md:grid-cols-3 grid-cols-2 gap-5">
            <article class="col-span-2 order-last md:order-first">
                <FeedsAndStatusCart :data="props.cart" />
                <OffersCard :items="{ data: cart?.cart_details }" />
            </article>
            <UserCard :student="cart?.student" class="col-span-2 md:col-span-1 order-first md:order-last" />
        </section>
        <DialogConfirm :loading="deleting" :show="!!confirmation" @close="confirmation = null" @confirm="onConfirmedDelete">
            Etes-vous sûr que vous voulez supprimer cette pannier
            <b class="text-red-500">{{ confirmation?.title }}</b>
            ?
        </DialogConfirm> -->
    </Page>
</template>
