/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 *
 * Config file for webpack
 *
 * This is in fact webpack.config.js but I renamed it builder.js
 * because I don't want any path clash with the web folder when
 * doing autocompletion.
 */
const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = {
  entry: {
    main: [
      './src/ts/common.ts',
      './src/ts/i18n.ts',
      './src/ts/steps-links.ts',
      './src/ts/tags.ts',
      './src/ts/admin.ts',
      './src/ts/edit.ts',
      './src/ts/jsoneditor.ts',
      './src/ts/team.ts',
      './src/ts/uploads.ts',
      './src/ts/todolist.ts',
      './src/ts/ucp.ts',
      './src/ts/view.ts',
      './src/ts/editusers.ts',
      './src/ts/search.ts',
      './src/ts/show.ts',
      './src/ts/sysconfig.ts',
      'bootstrap/js/src/alert.js',
      'bootstrap/js/src/button.js',
      'bootstrap/js/src/collapse.js',
      'bootstrap/js/src/dropdown.js',
      './src/ts/fontawesome.ts',
      './src/ts/mathjax.ts',
      'prismjs',
      // see list in tinymce.ts for codesample plugin settings
      'prismjs/components/prism-bash.js',
      'prismjs/components/prism-c.js',
      'prismjs/components/prism-cpp.js',
      'prismjs/components/prism-css.js',
      'prismjs/components/prism-fortran.js',
      'prismjs/components/prism-go.js',
      'prismjs/components/prism-java.js',
      'prismjs/components/prism-javascript.js',
      'prismjs/components/prism-julia.js',
      'prismjs/components/prism-latex.js',
      'prismjs/components/prism-lua.js',
      'prismjs/components/prism-makefile.js',
      'prismjs/components/prism-matlab.js',
      'prismjs/components/prism-perl.js',
      'prismjs/components/prism-python.js',
      'prismjs/components/prism-r.js',
      'prismjs/components/prism-ruby.js',
    ],
    jslibs: [
      './src/js/vendor/cornify.js',
      './src/js/vendor/jquery.rating.js',
      './src/js/vendor/keymaster.js',
    ],
    '3Dmol-notrack': [
      './src/ts/3Dmol-notrack.ts',
    ],
    'chemdoodle-canvas': [
      './src/js/chemdoodle-canvas.js',
    ],
  },
  // uncomment this to find where the error is coming from
  // makes the build slower
  //devtool: 'inline-source-map',
  mode: 'production',
  output: {
    filename: '[name].bundle.js',
    path: path.resolve(__dirname, 'web/assets')
  },
  optimization: {
    splitChunks: {
      chunks: 'all',
      name: 'vendor'
    },
    minimize: true,
    minimizer: [
      new CssMinimizerPlugin(),
      new TerserPlugin(),
    ],
  },
  watchOptions: {
      ignored: /node_modules/
  },
  plugins: [
    new MiniCssExtractPlugin(
      {
        filename: 'vendor.min.css',
      }
    ),
  ],
  resolve: {
    extensions: ['.ts', '.js'],
    fallback: {
      "stream": require.resolve("stream-browserify"),
      "timers": require.resolve("timers-browserify"),
    },
  },
  module: {
    rules:[
      // ts loader
      {
        test: /\.ts$/,
        use: {
          loader: 'ts-loader',
        },
      },
      {
        test: /\.css$/i,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
        ],
      },
      {
        test: /.(jpg|jpeg|png|svg)$/,
        type: 'asset/resource',
      },
      // expose jquery globally
      {
        test: require.resolve('jquery'),
        loader: 'expose-loader',
        options: {
          exposes: ['$', 'jQuery'],
        },
      },
      // expose key for keymaster globally
      {
        test: /keymaster.js/,
        loader: 'expose-loader',
        options: {
          exposes: 'key',
        },
      }
    ]
  }
};
