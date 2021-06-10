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

module.exports = {
  target: 'node',
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
