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
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const webpack = require('webpack');

module.exports = (env) => {
  return {
    entry: {
      main: [
        './src/scss/main.scss',
        './src/ts/common.ts',
        './src/ts/i18n.ts',
        './src/ts/steps-links.ts',
        './src/ts/chem-editor.ts',
        './src/ts/ketcher.jsx',
        './src/ts/ketcher-editor.jsx',
        './src/ts/compounds-table.jsx',
        './src/ts/tags.ts',
        './src/ts/admin.ts',
        './src/ts/profile.ts',
        './src/ts/edit.ts',
        './src/ts/scheduler.ts',
        './src/ts/team.ts',
        './src/ts/metadata.ts',
        './src/ts/uploads.ts',
        './src/ts/todolist.ts',
        './src/ts/ucp.ts',
        './src/ts/view.ts',
        './src/ts/revisions.ts',
        './src/ts/toolbar.ts',
        './src/ts/editusers.ts',
        './src/ts/show.ts',
        './src/ts/sysconfig.ts',
        'bootstrap/js/src/alert.js',
        'bootstrap/js/src/button.js',
        'bootstrap/js/src/collapse.js',
        'bootstrap/js/src/dropdown.js',
        './src/ts/mathjax.ts',
        'prismjs',
        './src/ts/prism-igor.ts',
        // see list in tinymce.ts for codesample plugin settings
        'prismjs/components/prism-bash.js',
        'prismjs/components/prism-c.js',
        'prismjs/components/prism-cpp.js',
        'prismjs/components/prism-css.js',
        'prismjs/components/prism-diff.js',
        'prismjs/components/prism-fortran.js',
        'prismjs/components/prism-go.js',
        'prismjs/components/prism-java.js',
        'prismjs/components/prism-javascript.js',
        'prismjs/components/prism-json.js',
        'prismjs/components/prism-julia.js',
        'prismjs/components/prism-latex.js',
        'prismjs/components/prism-lua.js',
        'prismjs/components/prism-makefile.js',
        'prismjs/components/prism-matlab.js',
        'prismjs/components/prism-perl.js',
        'prismjs/components/prism-python.js',
        'prismjs/components/prism-r.js',
        'prismjs/components/prism-ruby.js',
        'prismjs/components/prism-rust.js',
        'prismjs/components/prism-sql.js',
        'prismjs/components/prism-tcl.js',
        'prismjs/components/prism-vhdl.js',
        'prismjs/components/prism-yaml.js',
        './src/js/vendor/keymaster.js',
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
        name: 'vendor',
      },
      minimize: true,
      minimizer: [
        new CssMinimizerPlugin(),
        new TerserPlugin(),
      ],
    },
    plugins: [
      new MiniCssExtractPlugin(
        {
          filename: 'vendor.min.css',
        }
      ),
      // required to make process work in the browser
      new webpack.ProvidePlugin({
        process: 'process/browser',
      }),
    ],
    resolve: {
      extensions: ['.ts', '.js', '.jsx'],
      fallback: {
        // required by react 18
        process: require.resolve('process/browser'),
        util: require.resolve('util/'),
      },
    },
    module: {
      rules:[
        { // ts loader
          test: /\.ts$/,
          use: {
            loader: 'ts-loader',
            options: {
              // in prod, we don't have the types of some libs, use transpileOnly to avoid errors
              transpileOnly: env.production
              }
          },
        },
        { // CSS LOADER
          test: /\.css$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
          ],
        },
        {
        test: /\.jsx?$/,
        use: ["babel-loader"]
        },
        { // SASS loader
          test: /\.scss$/,
          type: 'asset/resource',
          generator: {
            filename: 'elabftw.min.css',
          },
          use: ['sass-loader'],
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
  }
};
