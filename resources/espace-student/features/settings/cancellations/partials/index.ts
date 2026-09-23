import { defineAsyncComponent } from 'vue';

export const CancellationItem = defineAsyncComponent(() => import('./CancellationItem.vue'));
export const CancelDetailsDrawer = defineAsyncComponent(() => import('./CancelDetailsDrawer.vue'));
