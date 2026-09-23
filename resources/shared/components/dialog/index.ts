import { defineAsyncComponent } from 'vue';
export const Dialog = defineAsyncComponent(() => import('./Dialog.vue'));
export const DialogAuth = defineAsyncComponent(() => import('./DialogAuth.vue'));
export const DialogConfirm = defineAsyncComponent(() => import('./DialogConfirm.vue'));
export const InlineConfirm = defineAsyncComponent(() => import('./InlineConfirm.vue'));
