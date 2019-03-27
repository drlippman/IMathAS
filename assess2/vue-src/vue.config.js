const path = require("path");

module.exports = {
  outputDir: path.resolve(__dirname, '../vue'),
  publicPath: process.env.NODE_ENV === 'production'
    ? '/assess2/'
    : '/',

  pluginOptions: {
    i18n: {
      locale: 'en',
      fallbackLocale: 'en',
      localeDir: 'locales',
      enableInSFC: false
    }
  }
  /* chainWebpack: config => {
    config.module
      .rule('i18n')
      .resourceQuery(/blockType=i18n/)
      .use('vue-i18n-loader2')
        .loader('vue-i18n-loader2')
        .options({
          quite: false,
          target: 'src/locales',
          character: 'utf-8',
        })
  }
  */
};
