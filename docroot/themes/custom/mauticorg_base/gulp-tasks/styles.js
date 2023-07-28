const utils = require('./_utils');
const sass = require('gulp-sass');
const sassGlob = require('gulp-sass-glob');
const postcss = require('gulp-postcss');
const plumber = require('gulp-plumber');
const autoprefixer = require('autoprefixer');

module.exports = function styles(gulp, config, bs) {
  const processors = [autoprefixer()];

  gulp.task('styles', () =>
    gulp
      .src(config.styles.source)
      .pipe(utils.onDev(plumber()))
      .pipe(sassGlob())
      .pipe(sass(config.styles.options).on('error', sass.logError))
      .pipe(postcss(processors))
      .pipe(utils.onDev(plumber.stop()))
      .pipe(gulp.dest(config.styles.destination))
      .pipe(utils.onDev(bs.stream()))
  );
};
