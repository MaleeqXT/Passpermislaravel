module.exports = {
    root: true,
    parser: 'vue-eslint-parser', // Use Vue parser
    parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
        parser: '@typescript-eslint/parser', // Use TypeScript parser inside `<script>`
    },
    extends: ['eslint:recommended', 'plugin:vue/vue3-recommended', 'plugin:@typescript-eslint/recommended', 'prettier'],
    rules: {
        'vue/multi-word-component-names': 0,
        'vue/no-v-html': 0,
        'vue/require-default-prop': 0,
        'vue/no-setup-props-destructure': 0,
        '@typescript-eslint/ban-ts-comment': 0,
        '@typescript-eslint/no-unused-expressions': 0,
        // '@typescript-eslint/no-unused-expressions': ['error'],
        '@typescript-eslint/no-explicit-any': 0,
        '@typescript-eslint/no-unused-vars': ['off', { argsIgnorePattern: '_^', varsIgnorePattern: '_^' }],
    },
    globals: {
        route: true,
        Ziggy: true,
        axios: true,
    },
    // overrides: [
    //     {
    //         files: ['*.js'],
    //         rules: {
    //             '@typescript-eslint/no-unused-vars': 0,
    //         },
    //     },
    // ],
    ignorePatterns: ['vendor/', 'node_modules/', 'public/', 'tests/'],
};
