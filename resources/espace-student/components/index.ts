import { defineAsyncComponent } from 'vue';

export const Sidebar = defineAsyncComponent(() => import('./Sidebar.vue'));
export const StudentDrawer = defineAsyncComponent(() => import('./StudentDrawer.vue'));
export const StudentHeader = defineAsyncComponent(() => import('./StudentHeader.vue'));
export const ReservationDetailsDrawer = defineAsyncComponent(() => import('./ReservationDetailsDrawer.vue'));
export const LocationDialog = defineAsyncComponent(() => import('./LocationDialog.vue'));
export const StudentProgressStatsCard = defineAsyncComponent(() => import('./StudentProgressStatsCard.vue'));
