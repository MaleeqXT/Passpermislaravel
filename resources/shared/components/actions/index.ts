import { defineAsyncComponent } from 'vue';
export const Button = defineAsyncComponent(() => import('./Button.vue'));
export const ButtonGroup = defineAsyncComponent(() => import('./ButtonGroup.vue'));
export const ButtonsList = defineAsyncComponent(() => import('./ButtonsList.vue'));
