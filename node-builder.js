const path = require('path');
require = require("esm")(module);

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
