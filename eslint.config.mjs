import globals from 'globals';
import js from '@eslint/js';
import stylisticJs from '@stylistic/eslint-plugin-js'
import ts from 'typescript-eslint';

export default [
    js.configs.all,
    ...ts.configs.recommended,
    {
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.es2023,
                ...globals.node
            }
        },
        plugins: {
            '@stylistic/js': stylisticJs,
        },
        rules: {
            '@stylistic/js/quotes': ['error', 'single'],
            'capitalized-comments': 'off',
            'class-methods-use-this': 'off',
            'func-names': 'off',
            'id-length': 'off',
            'indent': ['error', 4],
            'max-statements': 'off',
            'no-console': 'off',
            'no-continue': 'off',
            'no-magic-numbers': 'off',
            'no-plusplus': 'off',
            'no-shadow': 'off',
            'no-ternary': 'off',
            'no-undefined': 'off',
            'no-underscore-dangle': 'off',
            'one-var': 'off',
            'prefer-arrow-callback': 'off',
            'prefer-destructuring': 'off',
            'yoda': ['error', 'always']
        }
    },
    {
        ignores: ["assets/controllers/csrf_protection_controller.js"]
    }
];
