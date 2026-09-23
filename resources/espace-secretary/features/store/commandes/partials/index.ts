import { defineAsyncComponent } from 'vue';

export const RefundToolkit = defineAsyncComponent(() => import('./RefundToolkit.vue'));
export const CustomerCard = defineAsyncComponent(() => import('./CustomerCard.vue'));
export const StatsSection = defineAsyncComponent(() => import('./StatsSection.vue'));
export const StatSection = defineAsyncComponent(() => import('./StatSection.vue'));
export const OffersCard = defineAsyncComponent(() => import('./OffersCard.vue'));
export const SalesStats = defineAsyncComponent(() => import('./SalesStats.vue'));
