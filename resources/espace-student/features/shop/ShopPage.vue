<script setup lang="ts">
import { reactive } from 'vue';
import { PageMobile, ConfirmedPaymentModal, ActionsBottomBar } from '@common/components';
import { routes } from '@espace-student/routes';
import { HomeIcon } from '@adersolutions/icons';
import { moneyFormat } from '@shared/utils';
import { useCart } from '@shared/stores';
import { CartDrawer, OfferItem } from './partials';
import { SizeEnum } from '@shared/enums';
import type { ButtonType } from '@shared/types';
import type { OfferType } from '@common/types';
import { computed } from 'vue';

type PropsType = {
    offers: OfferType;
};

defineProps<PropsType>();

const cart = useCart();

const state = reactive({
    showDrawer: false,
});
const actions = computed<ButtonType[]>(() => [
    {
        variant: 'secondary',
        icon: HomeIcon,
        href: route(routes.dashboard.index),
    },
    {
        label: `Total payer ${moneyFormat(cart.prices.total || 0)}`,
        variant: 'warning',
        full: true,
        onAction: () => (state.showDrawer = true),
    },
]);
</script>
<template>
    <PageMobile title="Choisissez le plan qui vous convient" :width="SizeEnum.MD" class-wrapper="px-3">
        <ul class="grid grid-cols-1 gap-5 lg:grid-cols-3 mt-6">
            <OfferItem v-for="(item, idx) in offers?.data" :key="idx" :item="item" />
        </ul>

        <CartDrawer v-model="state.showDrawer" />
        <ActionsBottomBar no-marging :actions="actions" :show="!!cart.count" />
        <ConfirmedPaymentModal is-espace-student />
    </PageMobile>
</template>
