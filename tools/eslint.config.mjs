export default [
  {
    files: ['**/*.js'],
    languageOptions: {
      ecmaVersion: 2017,
      sourceType: 'script',
      globals: {
        window: 'readonly', document: 'readonly', console: 'readonly'
      }
    },
    linterOptions: { reportUnusedDisableDirectives: true },
    rules: {
      'no-undef': 'error',
      'no-unused-vars': 'error',
      'no-redeclare': 'error',
      'no-dupe-keys': 'error',
      'no-dupe-args': 'error',
      'no-unreachable': 'error',
      'no-cond-assign': 'error',
      'no-constant-condition': 'error',
      'no-empty': 'error',
      'no-extra-semi': 'error',
      'no-func-assign': 'error',
      'no-invalid-regexp': 'error',
      'no-irregular-whitespace': 'error',
      'no-obj-calls': 'error',
      'no-sparse-arrays': 'error',
      'use-isnan': 'error',
      'valid-typeof': 'error',
      'no-fallthrough': 'error',
      'no-self-assign': 'error',
      'no-delete-var': 'error',
      'eqeqeq': 'error',
      'no-shadow': 'warn'
    }
  }
];
