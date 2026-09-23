import { defineAsyncComponent } from 'vue';

export const CancellationItem = defineAsyncComponent(() => import('./CancellationItem.vue'));
export const DetailsAnnulationModal = defineAsyncComponent(() => import('./DetailsAnnulationModal.vue'));
