import { defineAsyncComponent } from 'vue';

export const CartDrawer = defineAsyncComponent(() => import('./CartDrawer.vue'));
export const OfferItem = defineAsyncComponent(() => import('./OfferItem.vue'));
