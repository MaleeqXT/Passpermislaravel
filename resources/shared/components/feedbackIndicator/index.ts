import { defineAsyncComponent } from 'vue';
export const Errors = defineAsyncComponent(() => import('./Errors.vue'));
export const Alerts = defineAsyncComponent(() => import('./Alerts.vue'));
export const InfosList = defineAsyncComponent(() => import('./InfosList.vue'));
export const EmptyState = defineAsyncComponent(() => import('./EmptyState.vue'));
export const Spinner = defineAsyncComponent(() => import('./Spinner.vue'));
export const Badge = defineAsyncComponent(() => import('./Badge.vue'));
export const PageLoading = defineAsyncComponent(() => import('./PageLoading.vue'));
