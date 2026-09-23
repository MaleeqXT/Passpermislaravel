import '../bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
// @ts-ignore
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/index.js';
import Layout from './Layout.vue';
import { createPinia } from 'pinia';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia();
createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    // @ts-ignore
    resolve: async (name) => {
        console.log(name);

        if (name?.includes('features/')) {
            const page = await resolvePageComponent(`./${name}.vue`, import.meta.glob('./features/**/*.vue'));
            page.default.layout = page.default.layout || Layout;
            return page;
        }
        return await resolvePageComponent(`../pages/${name}.vue`, import.meta.glob('../pages/**/*.vue'));
    },
    // @ts-ignore
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
