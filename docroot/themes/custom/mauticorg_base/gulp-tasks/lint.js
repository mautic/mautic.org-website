const stylelint = require('gulp-stylelint');
const gutil = require('gulp-util');
const eslint = require('gulp-eslint');
const plumber = require('gulp-plumber');
const utils = require('./_utils');

module.exports = function lint(gulp, config) {
  gulp.task('styles:lint', () =>
    gulp
      .src(config.styles.source)
      .pipe(utils.onDev(plumber()))
      .pipe(
        gutil.env.type === 'undefined'
          ? stylelint(config.stylelint.options)
          : stylelint(config.stylelint.optionsTest)
      )
      .pipe(utils.onDev(plumber.stop()))
  );

  gulp.task('scripts:lint', () =>
    gulp
      .src(config.scripts.source)
      .pipe(utils.onDev(plumber()))
      .pipe(eslint())
      .pipe(eslint.format())
      .pipe(eslint.failAfterError())
      .pipe(utils.onDev(plumber.stop()))
  );

  gulp.task('lint', gulp.parallel('styles:lint', 'scripts:lint'));
};
