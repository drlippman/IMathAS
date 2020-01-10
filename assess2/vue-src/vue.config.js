const path = require('path');

module.exports = {
  outputDir: path.resolve(__dirname, '../vue'),
  publicPath: process.env.NODE_ENV === 'production' ? './vue/' : '/',
  pluginOptions: {
    i18n: {
      locale: 'en',
      fallbackLocale: 'en',
      localeDir: 'locales',
      enableInSFC: false
    }
  },
  // delete HTML related webpack plugins
  filenameHashing: false,
  chainWebpack: config => {
    if (process.env.NODE_ENV === 'production') {
      config.plugins.delete('html');
      config.plugins.delete('preload');
      config.plugins.delete('prefetch');
      config.plugins.delete('copy');
    }
  },
  // in dev server mode, proxy all requests to localhost
  devServer: {
    proxy: 'http://localhost'
  },
  pages: {
    index: 'src/main.js',
    gbviewassess: 'src/gbviewassess/main.js'
  }
};
