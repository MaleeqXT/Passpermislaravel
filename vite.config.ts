import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';
import svgLoader from 'vite-svg-loader';
import { viteStaticCopy } from 'vite-plugin-static-copy';
// import { dependencies } from './package.json';
// import utwm from 'unplugin-tailwindcss-mangle/vite';

export default defineConfig(({ command }) => ({
    server: {
        // Keep this Laravel app separate from the other local dev server
        // currently using the IPv6 loopback address ([::1]:5173).
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '127.0.0.1',
            port: 5173,
        },
    },
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
    // This project has several independent Inertia entry points. Let the
    // dev server answer requests while Vite finishes scanning all of them.
    optimizeDeps: {
        holdUntilCrawlEnd: false,
    },
    plugins: [
        ...(command === 'build'
            ? [
                  viteStaticCopy({
                      targets: [
                          {
                              src: 'resources/assets',
                              dest: './../',
                          },
                      ],
                  }),
              ]
            : []),
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
}));
