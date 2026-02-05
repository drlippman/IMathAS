import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
import legacy from "@vitejs/plugin-legacy";
import checker from 'vite-plugin-checker'

const __dirname = path.dirname(fileURLToPath(import.meta.url));

function stripHashes() {
  /*
  This is a stupid hack.  I want to use query string for cache busting
  rather than file names, so I can git commit the build files more easily.
  But Vite doesn't like that, since the chunk file names get baked into the build.
  So, we hack our way around that by adding the query string to the build file, 
  which then gets stored that way, so this strips the query string from the stored file name.
  */
  return {
    name: 'strip-hashes',
    closeBundle(options, bundle) {
      const distPath = path.resolve(__dirname, '../vue/');
      const manifestPath = path.resolve(__dirname, '../vue/.vite/manifest.json');

      if (!fs.existsSync(manifestPath)) {
        return;
      }
      const manifest = JSON.parse(
        fs.readFileSync(manifestPath, 'utf-8')
      );
      Object.keys(manifest).forEach((key) => {
        const item = manifest[key];
        const oldFile = item.file;
        const newFile = oldFile.replace(/\?v=.*$/i, '');
        const oldPath = path.resolve(distPath, oldFile);
        const newPath = path.resolve(distPath, newFile);
        if (fs.existsSync(oldPath)) {
          fs.mkdirSync(path.dirname(newPath), { recursive: true });
          fs.renameSync(oldPath, newPath);
        }
      });
      console.log('✓ Query string hashes stripped from files');
    }
  }
}

function updatePhpFiles() {
  /*
  This updates the entry php files with the new cache-breaking hash.
  This is needed since the Vite-generated hash gets baked into the build files.
  */
  return {
    name: 'update-php-files',
    closeBundle(options, bundle) {
      const manifestPath = path.resolve(__dirname, '../vue/.vite/manifest.json');

      if (!fs.existsSync(manifestPath)) {
        return;
      }
      const manifest = JSON.parse(
        fs.readFileSync(manifestPath, 'utf-8')
      );

      // index
      let jsFile = manifest['index.html'].file;
      let legacyJsFile = manifest['index-legacy.html'].file;
      let cssFile = manifest['style.css'].file;

      // Update your PHP file
      let phpPath = path.resolve(__dirname, '../index.php');
      let phpContent = fs.readFileSync(phpPath, 'utf-8');
      phpContent = phpContent.replace(/js\/index\.js\?v=[\w\-]+/g, jsFile)
                            .replace(/js\/index-legacy\.js\?v=[\w\-]+/g, legacyJsFile)
                            .replace(/css\/style\.css\?v=[\w\-]+/g, cssFile);
      fs.writeFileSync(phpPath, phpContent);

      // gbviewassess
      jsFile = manifest['gbviewassess.html'].file;
      legacyJsFile = manifest['gbviewassess-legacy.html'].file;
      cssFile = manifest['style.css'].file;

      // Update your PHP file
      phpPath = path.resolve(__dirname, '../gbviewassess.php');
      phpContent = fs.readFileSync(phpPath, 'utf-8');
      phpContent = phpContent.replace(/js\/gbviewassess\.js\?v=[\w\-]+/g, jsFile)
                            .replace(/js\/gbviewassess-legacy\.js\?v=[\w\-]+/g, legacyJsFile)
                            .replace(/css\/style\.js\?v=[\w\-]+/g, cssFile);
      fs.writeFileSync(phpPath, phpContent);

      // Delete the .html files copied into /vue/
      let html1 = path.resolve(__dirname, '../vue/index.html');
      fs.unlinkSync(html1);
      let html2 = path.resolve(__dirname, '../vue/gbviewassess.html');
      fs.unlinkSync(html2);

      console.log('✓ PHP files updated with new asset hashes');
    }
  };
}

export default defineConfig(({ mode }) => {
  const isProduction = mode === 'production';
  const env = loadEnv(mode, process.cwd(), '');

  return {
    // allow reuse of the existing ENV variables
    envPrefix: "VUE_APP_",
    plugins: [
      vue(),
      checker({
        // runs lint in dev mode
        eslint: {
          lintCommand: 'eslint "./src/**/*.{js,vue}"'
        }
      }),
      legacy({
        // builds the legacy version
        targets: ["defaults", "not IE 11"],
      }),
      updatePhpFiles(),
      // strip the ?v=hash from the stored filenames; we only want the hash encoded
      // in the build, and in the php file
      stripHashes()
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
      // put all the css in one file; it's not that much
      cssCodeSplit: false,
      // needed for plugin above
      manifest: true,
      rollupOptions: {
        // our two entry points
        input: {
            index: path.resolve(__dirname, 'index.html'),
            gbviewassess: path.resolve(__dirname, 'gbviewassess.html')
        },
        output: {
          // hacky method - see stripHashes function above
          entryFileNames: `js/[name].js?v=[hash]`,
          chunkFileNames: `js/[name].js?v=[hash]`,
          assetFileNames: `css/[name].[ext]?v=[hash]`
        }
      }
    },
    
    // Base path. Seems to work without separate Production value
    base: './',
    // Vite processes the .html entry files no matter what, so this doesn't seem useful
    // publicDir: isProduction ? false : 'public',

    // Dev server proxy
    server: {
      port: 8080,
      hmr: true,
      proxy: env.VUE_APP_PROXY ? {
        // proxy everything except entry files and Vite/source stuff
        '^/(?!src|node_modules|@vite|@fs|@id|__|index.html|gbviewassess.html).*': {
          target: env.VUE_APP_PROXY,
          changeOrigin: true
        }
      } : undefined
    },
  };
});