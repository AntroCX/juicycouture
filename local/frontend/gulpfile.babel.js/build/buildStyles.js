import {src, dest}            from 'gulp';
import postcss                from 'gulp-postcss';
import sass                   from 'gulp-sass';
import sassGlob               from 'gulp-sass-glob';
import sourcemaps             from 'gulp-sourcemaps';
import {getDir, isProduction} from '../helpers/gets';
import cached                 from 'gulp-cached';

/**
 * Сборка и минификация стилей
 *
 * Scss + postcss + autoprefixer + cssnano
 *
 * @param cb
 */
export function buildStyles(cb) {
  let stream = src('./src/styles/*.scss');

  if (!isProduction()) {
    stream = stream.pipe(sourcemaps.init());
  }

  stream = stream.pipe(sassGlob())
    .pipe(sass()
      .on('error', e => console.log(e)))
    .pipe(postcss()
      .on('error', e => console.log(e)));

  if (!isProduction()) {
    stream = stream.pipe(sourcemaps.write('.'));
  }

  stream.pipe(dest(`${getDir()}/styles/`));

  src(['./src/styles/**/*.css'])
    .pipe(cached('css'))
    .pipe(dest(`${getDir()}/styles/`));

  cb ? cb() : 0;
}

