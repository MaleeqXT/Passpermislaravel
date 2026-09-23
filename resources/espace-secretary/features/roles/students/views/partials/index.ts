import { defineAsyncComponent } from 'vue';
export const InfosSection = defineAsyncComponent(() => import('./InfosSection.vue'));
export const CommentsSection = defineAsyncComponent(() => import('./CommentsSection.vue'));
export const CpfItem = defineAsyncComponent(() => import('./CpfItem.vue'));
export const RapportHours = defineAsyncComponent(() => import('./RapportHours.vue'));

export const CompetenciesGroup = defineAsyncComponent(() => import('./CompetenciesGroup.vue'));
export const Competency = defineAsyncComponent(() => import('./Competency.vue'));
