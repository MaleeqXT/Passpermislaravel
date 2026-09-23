import { defineAsyncComponent } from 'vue';
export const StudentForm = defineAsyncComponent(() => import('./StudentForm.vue'));
export const InscriptionTooltip = defineAsyncComponent(() => import('./InscriptionTooltip.vue'));
export const UserTooltip = defineAsyncComponent(() => import('./UserTooltip.vue'));
