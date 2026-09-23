import { defineAsyncComponent } from 'vue';

export const DrawerAdmin = defineAsyncComponent(() => import('./DrawerAdmin.vue'));
export const MonitorRapportDialog = defineAsyncComponent(() => import('./MonitorRapportDialog.vue'));
export const ProfileSection = defineAsyncComponent(() => import('./ProfileSection.vue'));
