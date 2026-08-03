/**
 * @author Marcel Bolten
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 *
 * Config file for building node apps with webpack
 *
 */
const path = require('path');
const mathjaxNewcmRoot = path.resolve(
  path.dirname(
    require.resolve('@mathjax/mathjax-newcm-font/js/svg.js'),
  ),
  '..',
);

module.exports = {
  target: 'node',
  resolve: {
    alias: {
      'mathjax-newcm-svg-dynamic': path.join(
        mathjaxNewcmRoot,
        'cjs',
        'svg',
        'dynamic',
      ),
    },
  },
  entry: {
    tex2svg: [
      './src/node/tex2svg.js',
    ],
  },
  mode: 'production',
  output: {
    filename: '[name].bundle.js',
    path: path.resolve(__dirname, 'src/node')
  },
};
