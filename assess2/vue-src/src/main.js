import { createApp, h } from 'vue';
import App from './App.vue';
import router from './router';
import { i18n } from './i18n';

// eslint-disable-next-line camelcase, no-undef
__webpack_public_path__ = process.env.NODE_ENV === 'production' ? window.imasroot + '/assess2/vue/' : '/';

createApp({
  i18n,
  render: () => h(App)
}).use(router).mount('#app');
