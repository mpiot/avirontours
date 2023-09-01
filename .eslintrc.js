module.exports = {
    env: {
        browser: true,
        es2021: true
    },
    extends: 'standard',
    overrides: [
        {
            env: {
                node: true
            },
            files: [
                '.eslintrc.{js,cjs}'
            ],
            parserOptions: {
                sourceType: 'script'
            }
        }
    ],
    parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module'
    },
    plugins: [
        'jsdoc'
    ],
    rules: {
        semi: ['error', 'always'],
        indent: ['error', 4],
        yoda: ['error', 'always'],
        'jsdoc/check-access': 'error',
        'jsdoc/check-alignment': 'error',
        'jsdoc/check-param-names': 'error',
        'jsdoc/check-property-names': 'error',
        'jsdoc/check-tag-names': 'error',
        'jsdoc/check-types': 'error',
        'jsdoc/check-values': 'error',
        'jsdoc/empty-tags': 'error',
        'jsdoc/implements-on-classes': 'error',
        'jsdoc/multiline-blocks': 'error',
        'jsdoc/no-multi-asterisks': 'error',
        'jsdoc/no-undefined-types': 'error',
        'jsdoc/require-jsdoc': 'error',
        'jsdoc/require-param': 'error',
        'jsdoc/require-param-name': 'error',
        'jsdoc/require-param-type': 'error',
        'jsdoc/require-property': 'error',
        'jsdoc/require-property-name': 'error',
        'jsdoc/require-property-type': 'error',
        'jsdoc/require-returns': 'error',
        'jsdoc/require-returns-check': 'error',
        'jsdoc/require-returns-type': 'error',
        'jsdoc/require-yields': 'error',
        'jsdoc/require-yields-check': 'error',
        'jsdoc/tag-lines': 'error',
        'jsdoc/valid-types': 'error',
        'jsdoc/check-indentation': 'error',
        'jsdoc/check-line-alignment': 'error',
        'jsdoc/check-syntax': 'error',
        'jsdoc/informative-docs': 'error',
        'jsdoc/match-description': 'error',
        'jsdoc/no-bad-blocks': 'error',
        'jsdoc/no-blank-block-descriptions': 'error',
        'jsdoc/no-defaults': 'error',
        'jsdoc/require-asterisk-prefix': 'error',
        'jsdoc/require-hyphen-before-param-description': 'error',
        'jsdoc/require-throws': 'error',
        'jsdoc/sort-tags': 'error'
    }
};
