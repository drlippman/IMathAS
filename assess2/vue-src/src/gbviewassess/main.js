import { createApp } from 'vue';
// import router from '../router';
import GBViewAssess from './GBViewAssess.vue';
import { i18n } from '../i18n';

// Vue.config.productionTip = false;

// Vue.use(VueResource)

createApp(GBViewAssess)
  .use(i18n)
  // .use(router)
  .mount('#app');
