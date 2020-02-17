import Vue from 'vue';
import GBViewAssess from './GBViewAssess.vue';
import { i18n } from '../i18n';

Vue.config.productionTip = false;

// Vue.use(VueResource)

new Vue({
  i18n,
  render: h => h(GBViewAssess)
}).$mount('#app');
