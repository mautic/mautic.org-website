const svgStore = require('gulp-svgstore');

module.exports = function svg(gulp, config) {
  gulp.task('svg', () =>
    gulp
      .src(config.svg.source)
      .pipe(svgStore())
      .pipe(gulp.dest(config.svg.destination))
  );
};
