const configureWebpack = require('./webpack.config.js');

module.exports = {
  configureWebpack,
  lintOnSave: false,
  runtimeCompiler: true,
};
