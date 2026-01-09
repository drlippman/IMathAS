import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { fluent } from './i18n';

createApp(App)
  .use(router)
  .use(fluent)
  .mount('#app');
