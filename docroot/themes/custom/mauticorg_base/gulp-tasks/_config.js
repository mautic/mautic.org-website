module.exports = {
  styles: {
    source: ['sass/**/*.scss'],
    destination: 'dist/css/',
    options: {
      outputStyle: 'expanded',
      includePaths: ['./node_modules']
    }
  },
  scripts: {
    source: ['ts/**/*.ts'],
    destination: 'dist/js/'
  },
  svg: {
    source: 'images/svg/**/*.svg',
    destination: 'dist/images/svg/'
  },
  stylelint: {
    options: {
      reporters: [
        {
          formatter: 'string',
          console: true
        }
      ]
    },
    optionsTest: {
      reporters: [
        {
          formatter: 'string',
          console: true,
          failAfterError: true
        }
      ]
    }
  },
  browserSync: {
    proxy: null,
    open: true,
    xip: false,
    logConnections: false
  }
};
