import Vue from 'vue';
import App from './App.vue';
import router from './router';
import i18n from './i18n';

Vue.config.productionTip = false;

// Vue.use(VueResource)
__webpack_public_path__ = process.env.NODE_ENV === 'production' ? window.imasroot + '/assess2/vue/' : '/';

new Vue({
  router,
  i18n,
  render: h => h(App)
}).$mount('#app');
