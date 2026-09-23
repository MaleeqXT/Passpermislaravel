import { defineAsyncComponent } from 'vue';
export const ExamForm = defineAsyncComponent(() => import('./ExamForm.vue'));
export const ExamFilters = defineAsyncComponent(() => import('./ExamFilters.vue'));
