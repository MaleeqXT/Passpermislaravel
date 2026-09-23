// resources/shims-vue.d.ts

// This declares the module for .vue files, allowing TypeScript to understand them.
declare module '*.vue' {
    import { DefineComponent } from 'vue';
    const component: DefineComponent<object, object, any>; // Adjust types as needed
    interface Window {
        route: any; // Adjust type as needed, e.g., import type { Route } from 'ziggy-js';
        // Pusher: any; // Uncomment when needed
        // Echo: any;   // Uncomment when needed
    }
    export default component;
}

// This augments Vue's ComponentCustomProperties interface.
// This is essential if you use ZiggyVue plugin in `ma
// Declaration for third-party Vue components (like vue-signature-pad)
// Keep this separate as it's not directly related to `route`
declare module 'vue-signature-pad' {
    import { DefineComponent } from 'vue';
    const VueSignaturePad: DefineComponent<{}, {}, any>;
    export { VueSignaturePad };
}
