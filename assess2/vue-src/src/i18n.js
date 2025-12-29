// import Vue from 'vue';
import { createI18n } from 'vue-i18n';
import { dateTimeFormats } from './dateTimeFormats';
import messages from '@intlify/unplugin-vue-i18n/messages';

export const i18n = createI18n({
  legacy: true,
  locale: 'en',
  fallbackLocale: 'en',
  messages: {en: messages.en},
  dateTimeFormats
});

const loadedLanguages = ['en']; // our default language that is preloaded

function setI18nLanguage (lang) {
  i18n.global.locale = lang;
  document.querySelector('html').setAttribute('lang', lang);
  return lang;
}

function loadLanguageAsync (lang) {
  // If the same language
  if (i18n.locale === lang) {
    return Promise.resolve(setI18nLanguage(lang));
  }

  // If the language was already loaded
  if (loadedLanguages.includes(lang)) {
    return Promise.resolve(setI18nLanguage(lang));
  }

  // If the language hasn't been loaded yet
  return import(`./locales/${lang}.json`).then(
    messages => {
      i18n.global.setLocaleMessage(lang, messages.default);
      loadedLanguages.push(lang);
      return setI18nLanguage(lang);
    }
  );
}

var docLang = document.getElementsByTagName('html')[0].getAttribute('lang').substring(0, 2);
if (docLang !== 'en') {
  loadLanguageAsync(docLang);
}
