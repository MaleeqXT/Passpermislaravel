import { defineAsyncComponent } from 'vue';
export const Page = defineAsyncComponent(() => import('./Page.vue'));
export const SecondSidebar = defineAsyncComponent(() => import('./SecondSidebar.vue'));
export const Drawer = defineAsyncComponent(() => import('./Drawer.vue'));
export const SideDrawer = defineAsyncComponent(() => import('./SideDrawer.vue'));
export const Card = defineAsyncComponent(() => import('./Card.vue'));
export const Back = defineAsyncComponent(() => import('./Back.vue'));
