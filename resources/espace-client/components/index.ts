import { defineAsyncComponent } from 'vue';
export const ClientButton = defineAsyncComponent(() => import('./ClientButton.vue'));
export const PageContainer = defineAsyncComponent(() => import('./PageContainer.vue'));
export const PriceCircle = defineAsyncComponent(() => import('./PriceCircle.vue'));
export const CartDrawer = defineAsyncComponent(() => import('./CartDrawer.vue'));
