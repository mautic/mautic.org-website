const gutil = require('gulp-util');
var gulpif = require('gulp-if');

module.exports = {
  onDev(task) {
    return gulpif(gutil.env.type === undefined, task);
  }
};
