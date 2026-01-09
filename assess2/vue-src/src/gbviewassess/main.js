import { createApp } from 'vue';
// import router from '../router';
import DummyRouter from '../components/DummyRouter.vue';
import GBViewAssess from './GBViewAssess.vue';
import { fluent } from '../i18n';

// Vue.config.productionTip = false;

// Vue.use(VueResource)

const app = createApp(GBViewAssess)
  .use(fluent);

app.component('RouterLink', DummyRouter);
app.mount('#app');
