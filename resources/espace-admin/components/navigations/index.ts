import { defineAsyncComponent } from 'vue';
export const Sidebar = defineAsyncComponent(() => import('./Sidebar.vue'));
export const Header = defineAsyncComponent(() => import('./Header.vue'));
