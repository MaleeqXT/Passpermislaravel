import { defineAsyncComponent } from 'vue';
export const FileLibrary = defineAsyncComponent(() => import('./FileLibrary.vue'));
export const SingleImageField = defineAsyncComponent(() => import('./SingleImageField.vue'));
export const MediaItem = defineAsyncComponent(() => import('./MediaItem.vue'));
