module.exports = {
  root: true,
  // Update env to include browser and es2021 for Vite apps
  env: {
    node: true,
    browser: true,
    es2021: true,
  },
  extends: [
    'plugin:vue/vue3-essential', // Upgraded from essential for better code quality
    'eslint:recommended',          // Standard base rules
  ],
  parserOptions: {
    ecmaVersion: 'latest',
  },
  rules: {
    'no-console': 'off',
    'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',
    
    // Your specific preferences
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
};