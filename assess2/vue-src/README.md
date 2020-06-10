# vue code

## Getting it to work

By default, the production build files from /assess2/vue/ are used, with
/assess2/index.php as the start page.

For development, add to your /config.php the lines

`$CFG['assess2-use-vue-dev'] = true;`

and

`$CFG['assess2-use-vue-dev-address'] = 'http://localhost:8080';`

which will adjust course page links to assessments to use the Vue dev
server.

You will also need to create the file: `assess2/vue-src/.env.local` with
the following contents:

```
VUE_APP_IMASROOT=http://localhost
VUE_APP_PROXY=http://localhost
```

If you have an `imasroot` set in your config.php other than the root directory,
append it to the address (no trailing slash) for VUE_APP_IMASROOT only.

Note you will also have to disable `$CFG['use_csrfp']` when using the above
option.

You may need to adjust /assess2/vue-src/public/index.html, editing the
`APIbase` line to point to have the correct web path to the assess2 directory.
This needs to point to your PHP server, not the Vue dev server.

## Project setup
```
npm install
```

### Compiles and hot-reloads for development
```
npm run serve
```

### Compiles and minifies for production
```
npm run build
```
If you modify any of the external javascript, be sure to also run this to
rebuild minified javascript files.  You may want to edit index.php to change
the v= date to force reload.
```
./buildmin.sh
```

### Run your tests
```
npm run test
```

### Lints and fixes files
```
npm run lint
```

### Customize configuration
See [Configuration Reference](https://cli.vuejs.org/config/).
