import { defineAsyncComponent } from 'vue';
export const Popup = defineAsyncComponent(() => import('./Popup.vue'));
export const Thumb = defineAsyncComponent(() => import('./Thumb.vue'));
export const Scrollable = defineAsyncComponent(() => import('./Scrollable.vue'));
export const PdfViewer = defineAsyncComponent(() => import('./PdfViewer.vue'));
