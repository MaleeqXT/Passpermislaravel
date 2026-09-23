import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';
import svgLoader from 'vite-svg-loader';
import { viteStaticCopy } from 'vite-plugin-static-copy';
// import { dependencies } from './package.json';
// import utwm from 'unplugin-tailwindcss-mangle/vite';

export default defineConfig({
    // server: {
    //     host: '192.168.3.8', // Bind to your local IP
    //     port: 5174, // Ensure this matches the port you're using
    //     cors: true, // Enable CORS for all origins
    // },
    resolve: {
        alias: {
            '@espace-admin': path.resolve('resources/espace-admin'),
            '@espace-secretary': path.resolve('resources/espace-secretary'),
            '@espace-monitor': path.resolve('resources/espace-monitor'),
            '@espace-student': path.resolve('resources/espace-student'),
            '@espace-client': path.resolve('resources/espace-client'),
            '@common': path.resolve('resources/common'),
            '@assets': path.resolve('resources/assets'),
            '@shared': path.resolve('resources/shared'),
        },
        extensions: ['.js', '.ts', '.vue', '.json'],
    },
    css: {
        preprocessorOptions: {
            scss: {
                silenceDeprecations: ['legacy-js-api'],
            },
        },
    },
    plugins: [
        viteStaticCopy({
            targets: [
                {
                    src: 'resources/assets',
                    dest: './../',
                },
            ],
        }),
        svgLoader({
            svgo: false,
        }),
        laravel({
            input: [
                'resources/espace-admin/index.ts',
                'resources/espace-secretary/index.ts',
                'resources/espace-client/index.ts',
                'resources/espace-monitor/index.ts',
                'resources/espace-student/index.ts',
            ],
            refresh: false,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // utwm(),
    ],
    build: {
        minify: 'esbuild',
        // cssCodeSplit: true,
        // outDir: 'dist', // your build folder
    },
});
