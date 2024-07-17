const path = require('path');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer')
    .BundleAnalyzerPlugin;

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
    //config.plugin("webpack-bundle-analyzer").use(BundleAnalyzerPlugin);
  },
  // see https://github.com/vuejs/vue-cli/issues/1768 for handling legacy naming
  configureWebpack () {
    const legacy = process.env.VUE_CLI_MODERN_BUILD ? "" : ".legacy";
    return {
      output: {
        filename: 'js/[name]' + legacy + '.js',
        chunkFilename: 'js/[name]' + legacy + '.js?v=[chunkhash]'
      }
    }
  },
  // in dev server mode, proxy all requests to localhost
  devServer: {
    proxy: process.env.VUE_APP_PROXY
  },
  pages: {
    index: 'src/main.js',
    gbviewassess: 'src/gbviewassess/main.js'
  }
};
