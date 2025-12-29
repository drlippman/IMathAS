import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import { fileURLToPath } from 'url'
import VueI18nPlugin from '@intlify/unplugin-vue-i18n/vite'
import legacy from "@vitejs/plugin-legacy";

const __dirname = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig(({ mode }) => {
  const isProduction = mode === 'production'
  const env = loadEnv(mode, process.cwd(), '')

  return {
    envPrefix: "VUE_APP_",
    plugins: [
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
      })
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
        '^/(?!src|node_modules|@vite|@fs|@id|__|index\.html|gbviewassess\.html).*': {
          target: env.VUE_APP_PROXY,
          changeOrigin: true
        }
      } : undefined
    },
  }
})