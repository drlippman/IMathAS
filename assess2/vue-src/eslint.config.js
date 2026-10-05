import js from '@eslint/js';
import globals from 'globals';
import pluginVue from 'eslint-plugin-vue';

export default [
  { ignores: ['node_modules/**', 'dist/**'] },
  js.configs.recommended,
  ...pluginVue.configs['flat/essential'],
  {
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.node,
        ...globals.browser
      }
    },
    rules: {
      'no-console': 'off',
      'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',

      semi: ['warn', 'always'],
      'no-labels': 'off',
      'vue/require-component-is': 'warn',
      'quote-props': ['warn', 'as-needed'],
      'no-prototype-builtins': 'off',
      'vue/multi-word-component-names': 'off',
      'vue/no-reserved-component-names': 'off',
      'no-var': 'off',
      'no-unused-vars': 'off',
      'object-shorthand': 'off',
      'dot-notation': 'off'
    }
  }
];
