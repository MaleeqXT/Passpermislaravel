import { defineAsyncComponent } from 'vue';
export const ZipForm = defineAsyncComponent(() => import('./ZipForm.vue'));
export const LieuForm = defineAsyncComponent(() => import('./LieuForm.vue'));
// export const ZipManager = defineAsyncComponent(() => import('./ZipManager.vue'));
// export const LieuxManager = defineAsyncComponent(() => import('./LieuxManager.vue'));
export const LieuZipItem = defineAsyncComponent(() => import('./LieuZipItem.vue'));

///
export const AreaFormDrawer = defineAsyncComponent(() => import('./AreaFormDrawer.vue'));
export const PlaceFormDrawer = defineAsyncComponent(() => import('./PlaceFormDrawer.vue'));
