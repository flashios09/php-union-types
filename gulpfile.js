const { watch, series } = require('gulp');
const connectPhp = require('gulp-connect-php');
const browserSync = require('browser-sync');
const chalk = require('chalk');
const run = require('gulp-shell').task;

function serve() {
  const httpPort = 8080;
  const connectPhpOptions = {
    port: httpPort,
  };
  const browserSyncOptions = {
    proxy: '127.0.0.1:' + httpPort,
    port: 3080,
    open: false,
    notify: false,
  };

  connectPhp.server(connectPhpOptions, () => browserSync(browserSyncOptions));

  watch('**/*.php').on('change', (file) => {
    console.log("[" + chalk.yellow('watch') + "] `" + chalk.yellow.bold(file) + "` changed !");
    browserSync.reload();
  });
}

async function kahlan() {
  run('composer test', { ignoreErrors: true })();
}

async function test() {
  watch('spec/*.spec.php').on('change', (file) => {
    console.log("[" + chalk.magenta('watch') + "] `" + chalk.magenta.bold(file) + "` changed !");
    kahlan();
  });
}

module.exports = {
  serve,
  test: series(kahlan, test),
};
