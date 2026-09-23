import { defineAsyncComponent } from 'vue';

export const ProposalItem = defineAsyncComponent(() => import('./ProposalItem.vue'));
export const ProposalDetailsDrawer = defineAsyncComponent(() => import('./ProposalDetailsDrawer.vue'));
