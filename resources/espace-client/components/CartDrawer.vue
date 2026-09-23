<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Drawer, EmptyState } from '@shared/components';
import { moneyFormat } from '@shared/utils';
import { useCart } from '@shared/stores';
import ClientButton from './ClientButton.vue';
import CartItem from './CartItem.vue';
import { routes } from '@espace-client/routes';

const show = defineModel({ type: Boolean });
// const tabs = [
//     { name: 'Carte de crédit', id: 'stripe' },
//     { name: 'PayPal', id: 'paypal' },
// ];
// const selectedTab = ref(tabs[0].id);
const cart = useCart();

const state = reactive({
    showSummary: true,
});

const onClose = () => {
    show.value = false;
};
</script>
<template>
    <Drawer :show="!!show" @close="onClose" title="Mes Offers">
        <EmptyState v-if="!cart.count" icon="CartIcon" title="Votre panier est vide" class="h-full flex-1 flex-col flex-center border-t">
            <p>Ajoutez des produits à votre panier pour passer à la caisse.</p>
        </EmptyState>

        <ul v-else role="list" class="flex flex-col divide-y text-xs border-t p-3 gap-3">
            <CartItem v-for="(item, idx) in cart.data" :key="idx" :item="item" />
        </ul>
        <section class="flex flex-col gap-3 pt-2 p-3 mt-auto bg-white">
            <div class="flex justify-between">
                <span class="text-lg font-semibold">Sous-total </span>
                <span class="text-lg font-semibold"> {{ moneyFormat(cart.prices.subTotal) }}</span>
            </div>
            <span class="font-light text-md">{{ cart.count }} Offres</span>
            <hr />
            <div class="flex justify-between">
                <span class="text-xl font-bold">Total : </span>
                <span class="text-2xl font-bold"> {{ moneyFormat(cart.prices.total) }}</span>
            </div>
            <p class="py-3 text-xs">
                pour appliquer le paiement par tranches, vous devez confirmer les offres du panier et procéder aux offres que vous
                choisissez dans la page suivante « page de paiement »
            </p>
            <ClientButton :href="route(routes.checkout)" :disabled="!cart.count" @click="show = false" variant="danger">
                Commander maintenant
            </ClientButton>
            <!-- <div v-if="cart.getTranche()">
                <CheckField
                    id="is-splite-active2"
                    v-model:checked="cart.state.isSpliteActive"
                    :label="`Activer le paiement en plusieur fois`"
                />
            </div> -->
        </section>
    </Drawer>
</template>
