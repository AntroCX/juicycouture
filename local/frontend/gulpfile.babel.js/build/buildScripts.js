import {src, dest, parallel}  from 'gulp';
import babel                  from 'gulp-babel';
import uglify                 from 'gulp-uglify';
import {getDir, isProduction} from '../helpers/gets';
import cached                 from 'gulp-cached';
import replace                from 'gulp-replace';
import webpack                from 'webpack-stream';
import {webpackConfig}        from '../webpack/webpack.config';

const fs = require('fs');

/**
 * Собирает бандлы
 *
 * @param cb  —  callback
 */
export function buildScriptsBundle(cb) {
  let stream = src('./src/scripts/debug.js');

  webpackConfig.mode    = process.env.NODE_ENV || 'development';
  webpackConfig.devtool = 'source-map';

  stream = stream
    .pipe(webpack(webpackConfig)
      .on('error', e => console.log(e)));

  if (isProduction()) {
    stream = stream
      .pipe(babel({presets: ['@babel/env']}))
      .pipe(uglify({mangle: false}))
      .pipe(replace('assets/', 'https://dkny.ru/local/templates/.default/assets/'));
  }

  stream.pipe(dest(`${getDir()}/scripts/`));

  cb ? cb() : 0;
}

// /**
//  * Копирует сторонние скрипты в билд
//  *
//  * @param cb  —  callback
//  */
export function buildVendorScripts(cb) {
  const vendorFolder = './src/scripts/vendor/';
  let fileList            = [];

  if (isProduction()) {
    fs.readdirSync(vendorFolder).forEach(file => {
      console.log(file);
      if (file.indexOf('.min.') !== -1) {
        fileList.push(vendorFolder + file);
      }
    });
  } else {
    fileList = './src/scripts/vendor/**/*.js';
  }

  src(fileList)
    .pipe(cached('scriptsVendor'))
    .pipe(dest(`${getDir()}/scripts/vendor/`));

  cb ? cb() : 0;
}

/**
 * Копирует api
 *
 * @param cb  —  callback
 */
export function buildApiScripts(cb) {
  src(['./src/scripts/api.js'])
    .pipe(cached('scriptsAPI'))
    .pipe(dest(`${getDir()}/scripts/`));

  cb ? cb() : 0;
}

exports.buildScripts = parallel(
  buildScriptsBundle,
  buildVendorScripts,
  buildApiScripts
);