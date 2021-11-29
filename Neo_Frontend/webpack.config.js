const path = require('path');
const MomentLocalesPlugin = require('moment-locales-webpack-plugin');

module.exports = {
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src/'),
    },
    extensions: ['.js', '.vue'],
  },
  devServer: {
    disableHostCheck: true,
    hot: true,
    watchOptions: {
      aggregateTimeout: 300,
      poll: true,
    },
    headers: {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
      'Access-Control-Allow-Headers': 'X-Requested-With, Content-type, Authorization',
      'Access-Control-Allow-Credentials': 'true',
      Vary: 'Origin',
    },
  },
  plugins: [
    new MomentLocalesPlugin({
      localesToKeep: ['en', 'de', 'tr'],
    }),
  ],
};
