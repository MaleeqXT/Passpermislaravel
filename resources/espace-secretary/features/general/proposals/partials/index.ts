import { defineAsyncComponent } from 'vue';
export const ProposalItem = defineAsyncComponent(() => import('./ProposalItem.vue'));
export const ProposalFormDrawer = defineAsyncComponent(() => import('./ProposalFormDrawer.vue'));
