import { defineAsyncComponent } from 'vue';
export const Filters = defineAsyncComponent(() => import('./Filters.vue'));
export const DataTable = defineAsyncComponent(() => import('./DataTable.vue'));
export const Pagination = defineAsyncComponent(() => import('./Pagination.vue'));
