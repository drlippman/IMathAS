module.exports = {
  root: true,
  env: {
    node: true
  },
  'extends': [
    'plugin:vue/essential',
    '@vue/standard'
  ],
  rules: {
    'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'off',
    'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',
    'semi': ['warn', 'always'],
    'no-labels': 'off',
    'quote-props': ['warn','as-needed'],
    'no-prototype-builtins': 'off'
  },
  parserOptions: {
    parser: 'babel-eslint'
  }
};
