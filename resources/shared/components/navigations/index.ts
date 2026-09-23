import { defineAsyncComponent } from 'vue';
export const Frame = defineAsyncComponent(() => import('./Frame.vue'));
export const NavMenu = defineAsyncComponent(() => import('./NavMenu.vue'));
export const NavSubMenu = defineAsyncComponent(() => import('./NavSubMenu.vue'));
