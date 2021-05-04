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
