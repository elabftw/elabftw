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
const MinimizerPlugin = require('minimizer-webpack-plugin');
const webpack = require('webpack');
const sveltePreprocess = require('svelte-preprocess');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const mathjaxNewcmRoot = path.resolve(
  path.dirname(
    require.resolve('@mathjax/mathjax-newcm-font/js/svg.js'),
  ),
  '..',
);

module.exports = (env, argv) => {
  const mode = argv.mode ?? 'production';
  const isDevelopment = mode === 'development';
  return {
    entry: {
      main: [
        './src/scss/main.scss',
        './src/ts/common.ts',
        './src/ts/page-loader.ts',
        './src/ts/create-new.ts',
        './src/ts/i18n.ts',
        './src/ts/dspace.ts',
        './src/ts/tags.ts',
        './src/ts/admin.ts',
        './src/ts/metadata.ts',
        './src/ts/show.ts',
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
        'prismjs/components/prism-markup-templating.js', // necessary for php
        'prismjs/components/prism-matlab.js',
        'prismjs/components/prism-perl.js',
        'prismjs/components/prism-php.js',
        'prismjs/components/prism-python.js',
        'prismjs/components/prism-r.js',
        'prismjs/components/prism-ruby.js',
        'prismjs/components/prism-rust.js',
        'prismjs/components/prism-sql.js',
        'prismjs/components/prism-tcl.js',
        'prismjs/components/prism-vhdl.js',
        'prismjs/components/prism-yaml.js',
      ],
      spreadsheet: [
        './src/ts/spreadsheet-editor.jsx',
        './src/ts/spreadsheet-utils.ts',
      ],
    },
    // faster but less precise source map
    devtool: isDevelopment ? 'cheap-module-source-map' : false,
    mode,
    output: {
      filename: '[name].bundle.js',
      path: path.resolve(__dirname, 'web/assets')
    },
    optimization: {
      splitChunks: {
        chunks: 'async',
      },
      minimize: !isDevelopment,
      minimizer: [
        // use parallel option to reduce RAM usage from parallelization during minimization
         new MinimizerPlugin({
          test: /\.[cm]?js(\?.*)?$/i,
          parallel: 4,
          minify: MinimizerPlugin.terserMinify,
        }),
        new MinimizerPlugin({
          test: /\.css(\?.*)?$/i,
          parallel: 4,
          minify: MinimizerPlugin.cssnanoMinify,
          minimizerOptions: {
            preset: 'default',
          },
        }),
      ],
    },
    plugins: [
      new MiniCssExtractPlugin(
        {
          filename: '[name].min.css',
          chunkFilename: '[name].min.css',
          insert: function(linkTag) {
            var mainStylesheet = document.getElementById('main-stylesheet');
            if (mainStylesheet && mainStylesheet.parentNode) {
              mainStylesheet.parentNode.insertBefore(linkTag, mainStylesheet);
              return;
            }
            document.head.appendChild(linkTag);
          },
        }
      ),
      // required to make process work in the browser
      new webpack.ProvidePlugin({
        process: 'process/browser.js',
      }),
      new CopyWebpackPlugin({
        patterns: [
          {
            from: path.resolve(
              __dirname,
              '/run/elabftw/yarn/unplugged/indigo-ketcher-npm-*/node_modules/indigo-ketcher/**/*.wasm',
            ),
            to: '[name][ext]',
            noErrorOnMissing: false,
          },
          {
            from: path.join(mathjaxNewcmRoot, 'svg', 'dynamic'),
            to: 'mathjax/mathjax-newcm-font/svg/dynamic',
            noErrorOnMissing: false,
          },
        ],
      }),
    ],
    resolve: {
      extensions: ['.ts', '.js', '.jsx', '.svelte'],
      conditionNames: ['svelte', 'browser', '...'],
      mainFields: ['svelte', 'browser', 'module', 'main'],
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
              transpileOnly: !isDevelopment,
              }
          },
        },
        // svelte loader
        {
          test: /\.svelte$/,
          use: {
            loader: 'svelte-loader',
            options: {
              emitCss: true,
              preprocess: sveltePreprocess({
                // preserve value imports used only in Svelte markup, such as child components.
                // without this, TypeScript may remove them as unused before Svelte compiles the template
                typescript: {
                  compilerOptions: {
                    verbatimModuleSyntax: true,
                  },
                },
              }),
            },
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
          include: path.resolve(__dirname, 'src'),
          use: ["babel-loader"]
        },
        { // SASS loader
          test: /\.scss$/,
          use: [
            MiniCssExtractPlugin.loader,
            {
              loader: 'css-loader',
              options: {
                url: false,
              },
            },
            'sass-loader',
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
        }
      ]
    }
  }
};
