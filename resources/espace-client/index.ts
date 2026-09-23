import '../bootstrap.js';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import Layout from './Layout.vue';
import { createPinia } from 'pinia';
// @ts-ignore - ZiggyVue is not recognized
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/index.js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia();
createInertiaApp({
    title: (title) => `${title} - ${appName} `,
    // @ts-ignore
    resolve: async (name) => {
        if (name?.includes('features/')) {
            const page: any = await resolvePageComponent(`./${name}.vue`, import.meta.glob('./features/**/*.vue'));
            if (name !== 'features/checkout/ChekoutPage') {
                page.default.layout = page.default.layout || Layout;
            }
            return page;
        }
        const [prefix, suffix] = name.split('/');
        const newName = `${prefix.toLocaleLowerCase()}/${suffix}`;
        return await resolvePageComponent(`./features/${newName}.vue`, import.meta.glob('./features/**/*.vue'));
        // return await resolvePageComponent(`../pages/${name}.vue`, import.meta.glob('../pages/**/*.vue'));
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
