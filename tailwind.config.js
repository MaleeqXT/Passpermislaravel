// import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/**/*.vue',
        './resources/**/*.ts',
        './resources/**/*.js',
        // './resources/common/**/*.js',
        // './resources/shared/**/*.js',
        // './resources/assets/**/*.svg',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: [
                    'ui-sans-serif',
                    'system-ui',
                    'sans-serif',
                    'Apple Color Emoji',
                    'Segoe UI Emoji',
                    'Segoe UI Symbol',
                    'Noto Color Emoji',
                ],
                pdf: ['Helvetica', 'Arial', 'sans-serif'],
                outfit: ['sans-serif'],
            },
            maxWidth: {
                lg: '768px',
            },
            fontSize: {
                '3xs': '10px',
                '2xs': '11px',
                sm: '13px',
                md: '14px',
                base: '15px',
                lg: '18px',
                custom: 'var(--gsize,13px)',
            },
            zIndex: {
                1: '1',
                900: '900',
            },
            animation: {
                spin: 'spin 1s linear infinite',
                pulse: 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
            borderRadius: {
                lg: '0.6rem',
            },
            colors: {
                gray: {
                    50: '#f9fafb',
                    100: '#f2f4f5',
                    200: '#eaebed',
                    300: '#c7ccd1',
                    400: '#929ba2',
                    500: '#6a737d',
                    600: '#505861',
                    700: '#3a4148',
                    800: '#24292e',
                    900: '#12181c',
                    950: '#0a0f12',
                },
                black: '#011319',
                dark: '#02161D',
                dark2: '#2c4044',
                custom: 'var(--customcolor,#02161D)',
                primary: '#85be55', // @1DB488
            },

            boxShadow: {
                inner: 'inset 0 1px 0px 0.5px rgba(0, 0, 0, 0.05)',
                down: '0px 3px 5px -2px rgb(0 0 0 / 10%)',
                up: '0 -2px 7px 0px rgb(47 50 53 / 10%)',
                box: '1px 2px 1px 0px rgb(53 53 53 / 15%)',
            },

            backgroundImage: {
                gr: 'radial-gradient(var(--tw-gradient-stops))',
                empty: 'repeating-linear-gradient(45deg, #efefef, #efefef 7px, #FFF 7px, #fff 13px)',
            },
        },
    },
    safelist: [
        {
            pattern: /status-(info|success|warning|danger|dark|default|alert)/,
        },
        {
            pattern: /tab-(info|success|warning|danger|dark|default|alert)/,
        },
        {
            pattern: /^btn-/,
            variants: ['sm', 'md', 'lg', 'xl'],
            // pattern: /btn-(info|success|warning|danger|dark|secondary|primary|orange)/,
        },
        {
            pattern: /order-(1|2|3)/,
            variants: ['md'],
        },
        {
            pattern: /col-start-(1|2|3)/,
            variants: ['md'],
        },
    ],
    plugins: [forms, typography],
};
