const config = require('./gulp-tasks/_config');
const gulp = require('gulp');
const bs = require('browser-sync').create();

require('./gulp-tasks/styles')(gulp, config, bs);
require('./gulp-tasks/scripts')(gulp, config, bs);
require('./gulp-tasks/svg')(gulp, config, bs);
require('./gulp-tasks/watch')(gulp, config, bs);
require('./gulp-tasks/lint')(gulp, config);
require('./gulp-tasks/default')(gulp, config);
