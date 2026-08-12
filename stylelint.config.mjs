export default {
    extends: 'stylelint-config-standard-scss',
    rules: {
        // Paragraph breaks inside `//` comments are blank `//` lines throughout the codebase.
        'scss/comment-no-empty': null,

        // Variable groups are separated by blank lines rather than one comment per variable.
        'scss/dollar-variable-empty-line-before': null,

        // No autoprefixer in the build: the few vendor prefixes are written by hand, deliberately.
        'property-no-vendor-prefix': null,

        // `$breadcrumb-divider: quote(">")` is Bootstrap's own documented override form.
        'scss/function-quote-no-quoted-strings-inside': null,
        'scss/no-global-function-names': null,
    },
};
