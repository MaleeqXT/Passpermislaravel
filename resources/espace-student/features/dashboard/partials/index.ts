import { defineAsyncComponent } from 'vue';

export const DetailsDialog = defineAsyncComponent(() => import('./DetailsDialog.vue'));
export const TrainingProgress = defineAsyncComponent(() => import('./TrainingProgress.vue'));
export const SeanceReservationList = defineAsyncComponent(() => import('./SeanceReservationList.vue'));
