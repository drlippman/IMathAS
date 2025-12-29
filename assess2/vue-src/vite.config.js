import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
import VueI18nPlugin from '@intlify/unplugin-vue-i18n/vite';
import legacy from "@vitejs/plugin-legacy";
import eslintPlugin from 'vite-plugin-eslint';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

function updatePhpFiles() {
  return {
    name: 'update-php-files',
    closeBundle(options, bundle) {
      const manifest = JSON.parse(
        fs.readFileSync(path.resolve(__dirname, '../vue/.vite/manifest.json'), 'utf-8')
      );

      // index
      let jsFile = manifest['index.html'].file;
      let legacyJsFile = manifest['index-legacy.html'].file;
      let cssFile = manifest['style.css'].file;

      // Update your PHP file
      let phpPath = path.resolve(__dirname, '../index.php');
      let phpContent = fs.readFileSync(phpPath, 'utf-8');
      phpContent = phpContent.replace(/js\/index-\w+\.js/g, jsFile)
                            .replace(/js\/index-legacy-\w+\.js/g, legacyJsFile)
                            .replace(/css\/style-\w+\.css/g, cssFile);
      fs.writeFileSync(phpPath, phpContent);

      // gbviewassess
      jsFile = manifest['gbviewassess.html'].file;
      legacyJsFile = manifest['gbviewassess-legacy.html'].file;
      cssFile = manifest['style.css'].file;

      // Update your PHP file
      phpPath = path.resolve(__dirname, '../gbviewassess.php');
      phpContent = fs.readFileSync(phpPath, 'utf-8');
      phpContent = phpContent.replace(/js\/gbviewassess-\w+\.js/g, jsFile)
                            .replace(/js\/gbviewassess-legacy-\w+\.js/g, legacyJsFile)
                            .replace(/css\/style-\w+\.css/g, cssFile);
      fs.writeFileSync(phpPath, phpContent);
      console.log('✓ PHP files updated with new asset hashes');
    }
  };
}

export default defineConfig(({ mode }) => {
  const isProduction = mode === 'production';
  const env = loadEnv(mode, process.cwd(), '');

  return {
    envPrefix: "VUE_APP_",
    plugins: [
      eslintPlugin(),
      vue(),
      // i18n plugin
      VueI18nPlugin({
        include: [path.resolve(__dirname, './src/locales/en.json')],
        compositionOnly: false,
        runtimeOnly: false, 
        fullInstall: true,
      }),
      legacy({
        targets: ["defaults", "not IE 11"],
      }),
      updatePhpFiles()
    ],
    
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './src'),
      },
      dedupe: ['vue']
    },
    
    // Output directory
    build: {
      outDir: path.resolve(__dirname, '../vue'),
      emptyOutDir: true,
      cssCodeSplit: false,
      manifest: true,
      rollupOptions: {
        input: {
            index: path.resolve(__dirname, 'index.html'),
            gbviewassess: path.resolve(__dirname, 'gbviewassess.html')
        },
        output: {
          entryFileNames: `js/[name]-[hash].js`,
          chunkFileNames: `js/[name]-[hash].js`,
          assetFileNames: `css/[name]-[hash].[ext]`
        }
      }
    },

    /*optimizeDeps: {
      include: ['vue', 'vue-router', 'vue-i18n'],
      force: true,
      exclude: ['@vue/compiler-sfc']
    },*/
    
    // Base path
    base: './',
    //publicDir: isProduction ? false : 'public',

    // Dev server proxy
    server: {
      port: 8080,
      hmr: true,
      proxy: env.VUE_APP_PROXY ? {
        '^/(?!src|node_modules|@vite|@fs|@id|__|index.html|gbviewassess.html).*': {
          target: env.VUE_APP_PROXY,
          changeOrigin: true
        }
      } : undefined
    },
  };
});