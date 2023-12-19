module.exports = {
  root: true,
  env: {
    node: true
  },
  extends: [
    'plugin:vue/vue3-essential',
    '@vue/standard'
  ],
  rules: {
    'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'off',
    'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',
    semi: ['warn', 'always'],
    'no-labels': 'off',
    'vue/require-component-is': 'warn',
    'quote-props': ['warn', 'as-needed'],
    'no-prototype-builtins': 'off',
    'vue/multi-word-component-names': 'off',
    'vue/no-reserved-component-names': 'off',
    'no-var': 'off',
    'object-shorthand': 'off',
    'dot-notation': 'off'
  },
  parserOptions: {
    parser: '@babel/eslint-parser'
  }
};
