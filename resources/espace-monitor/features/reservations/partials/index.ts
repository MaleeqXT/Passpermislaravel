import { defineAsyncComponent } from 'vue';
export const ScheduleBlock = defineAsyncComponent(() => import('./ScheduleBlock.vue'));
export const ProposalDetailsDrawer = defineAsyncComponent(() => import('./ProposalDetailsDrawer.vue'));
export const RecurringHoursModal = defineAsyncComponent(() => import('./recurring/RecurringHoursModal.vue'));
