<script setup>
import { ref } from 'vue';
import { useCart } from '@shared/stores';
import { Button, CheckField } from '@shared/components';
import { moneyFormat } from '@shared/utils';
import { routes } from '@espace-client/routes';

const cart = useCart();
const loading = ref(false);

// Reactivity for input fields
const pay = () => {
    if (cart.loggedUser) {
        loading.value = true;
        axios
            .get(route(routes.api.student.commande.process), {
                params: {
                    amount: cart.prices.total,
                    balance: cart.prices.balance,
                },
            })
            .then((response) => {
                window.location.href = response.data.approval_link;
            })
            .catch((error) => {
                console.error(error);
            })
            .finally(() => {
                loading.value = false;
            });
    } else {
        cart.state.authModal = true;
    }
};
</script>

<template>
    <div class="flex flex-col mb-3">
        <div class="w-full flex items-center justify-center">
            <img src="/assets/images/payments/paypal-cover.png" alt="paypal image" class="h-60" />
        </div>
        <h1 class="text-lg font-semibold mb-2">Se connecter en utilisant votre compte Paypal</h1>
        <div class="flex flex-col gap-2 h-max justify-end">
            <slot />
            <Button class="h-12" :loading="loading" full variant="warning" type="button" @click="pay">
                <slot name="btn"> Connecter et payer avec Paypal ({{ moneyFormat(cart.prices.total) }}) </slot>
            </Button>
        </div>
    </div>
</template>
