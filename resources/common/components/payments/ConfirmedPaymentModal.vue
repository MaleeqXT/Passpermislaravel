<script setup lang="ts">
import { Button, Dialog } from '@shared/components';
import { routes } from '@espace-student/routes';
import { useCart } from '@shared/stores';
import { ReceiptDollarIcon } from '@adersolutions/icons';
const props = defineProps({
    isEspaceStudent: Boolean,
});
const cart = useCart();
const close = () => {
    if (props.isEspaceStudent) {
        cart.state.sale = null;
    }
};
</script>

<template>
    <Dialog :show="!!cart.state.sale" max-width="xs" z-index="z-[1100]" @close="cart.state.sale = null">
        <div class="flex flex-col relative bg-primary p-2 h-full text-center">
            <p class="text-2xl text-white font-bold py-6 flex-center">Merçi pour votre achat.</p>
            <div class="flex flex-col w-full items-center justify-center h-full bg-white rounded-xl">
                <ReceiptDollarIcon class="w-20 h-20 p-5 bg-yellow-50 rounded-full text-yellow-500 my-5" />
                <p>Votre paiement a été confirmé avec succès.</p>
                <p>Vous pouvez maintenant accéder à votre compte pour suivre votre commande.</p>

                <div class="flex gap-3 w-full p-2 mt-5">
                    <Button :href="route(routes.dashboard.index)" self variant="warning" full @click="close"> Tableau de bord </Button>
                    <Button
                        v-if="cart.state.sale?.id"
                        :href="route(routes.commandes.show, cart.state.sale?.id)"
                        :self="!isEspaceStudent"
                        variant="dark"
                        full
                        @click="close"
                    >
                        Suivre la commande
                    </Button>
                </div>
            </div>
        </div>
    </Dialog>
</template>
