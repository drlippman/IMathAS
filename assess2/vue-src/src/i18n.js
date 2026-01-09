// import Vue from 'vue';
import { FluentBundle, FluentResource } from '@fluent/bundle';
import { createFluentVue } from 'fluent-vue';

import enMessages from './locales/en.ftl?raw';
const enBundle = new FluentBundle('en');
enBundle.addResource(new FluentResource(enMessages));
export const fluent = createFluentVue({
  bundles: [enBundle]
});

async function loadLanguageAsync (locale) {
  // Dynamic import: Vite will create a separate JS chunk for each .ftl file
  // We use '?raw' to tell Vite to import the file content as a string
  const messages = await import(`./locales/${locale}.ftl?raw`);
  
  const newBundle = new FluentBundle(locale);
  newBundle.addResource(new FluentResource(messages.default));
  
  // 3. Swap the bundles to trigger a global re-render
  fluent.bundles = [newBundle, enBundle];
}

var docLang = document.getElementsByTagName('html')[0].getAttribute('lang').substring(0, 2);
if (docLang !== 'en') {
  loadLanguageAsync(docLang);
}
