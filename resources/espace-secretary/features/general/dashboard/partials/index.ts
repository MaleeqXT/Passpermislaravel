import { defineAsyncComponent } from 'vue';

export const LastCommandesCard = defineAsyncComponent(() => import('./LastCommandesCard.vue'));
export const LastReservationCard = defineAsyncComponent(() => import('./LastReservationCard.vue'));
