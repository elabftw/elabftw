/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
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
const HtmlWebpackPlugin = require('html-webpack-plugin');
const path = require('path');
const webpack = require('webpack');

module.exports = {
  entry: {
    main: [
      './src/ts/common.ts',
      './src/ts/steps-links.ts',
      './src/ts/tabs.ts',
      './src/ts/tags.ts',
      'bootstrap/js/src/alert.js',
      'bootstrap/js/src/button.js',
      'bootstrap/js/src/collapse.js',
      'bootstrap/js/src/dropdown.js',
      './src/ts/fontawesome.ts',
      // mathjax config must be loaded before mathjax lib
      './web/app/js/src/mathjax-config.js',
      // load tex with all the extensions
      'mathjax/es5/tex-svg-full.js',
      'prismjs',
      // see list in edit.js tinymce codesample plugin settings
      'prismjs/components/prism-bash.js',
      'prismjs/components/prism-c.js',
      'prismjs/components/prism-cpp.js',
      'prismjs/components/prism-css.js',
      'prismjs/components/prism-fortran.js',
      'prismjs/components/prism-go.js',
      'prismjs/components/prism-markup.js',
      'prismjs/components/prism-java.js',
      'prismjs/components/prism-javascript.js',
      'prismjs/components/prism-julia.js',
      'prismjs/components/prism-latex.js',
      'prismjs/components/prism-makefile.js',
      'prismjs/components/prism-matlab.js',
      'prismjs/components/prism-perl.js',
      'prismjs/components/prism-python.js',
      'prismjs/components/prism-r.js',
      'prismjs/components/prism-ruby.js',
    ],
    admin: './src/ts/admin.ts',
    changepass: './src/ts/change-pass.ts',
    edit: [
      './src/ts/edit.ts',
      './src/ts/jsoneditor.ts',
    ],
    editusers: './src/ts/editusers.ts',
    profile: './src/ts/profile.ts',
    search: './src/ts/search.ts',
    show: './src/ts/show.ts',
    sysconfig: './src/ts/sysconfig.ts',
    team: './src/ts/team.ts',
    todolist: './src/ts/todolist.ts',
    ucp: './src/ts/ucp.ts',
    uploads: './src/ts/uploads.ts',
    view: [
      './src/ts/view.ts',
      './src/ts/comments.ts',
    ],
  },
  plugins: [
    // only load the moment locales that we are interested in
    new webpack.ContextReplacementPlugin(/moment[\\\/]locale$/, /^\.\/(ca|de|en|es|fr|it|id|ja|kr|nl|pl|pt|pt-br|ru|sk|sl|zh-cn)$/),
    // insert the paths of the bundles into the html template
    // this creates a web/app/js/script-tags.html file that we can copy paste into the real html template in src/template/head.html
    new HtmlWebpackPlugin({
      filename: 'scripts-tags.html',
      template: 'src/js/scripts-tags.html',
      inject: false,
      // we only want the vendors chunks
      excludeChunks: ['admin', 'changepass', 'edit', 'editusers', 'profile', 'search', 'show', 'sysconfig', 'team', 'todolist', 'ucp', 'uploads', 'view'],
    }),
  ],
  mode: 'production',
  output: {
    filename: '[name].bundle.js',
    path: path.resolve(__dirname, 'web/app/js')
  },
  optimization: {
    splitChunks: {
      chunks: 'all',
      minSize: 30000,
      maxSize: 0,
      minChunks: 1,
      maxAsyncRequests: 6,
      maxInitialRequests: 4,
      automaticNameDelimiter: '~',
      automaticNameMaxLength: 50,
      cacheGroups: {
        vendors: {
          test: /[\\/]node_modules[\\/]/,
          priority: -10
        },
        default: {
          minChunks: 2,
          priority: -20,
          reuseExistingChunk: true
        }
      }
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
      // transpile things with babel so javascript works with Edge
      {
        test: /\.m?js$/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env']
          }
        }
      },
      // expose jquery and moment globally
      {
        test: require.resolve('jquery'),
        use: [
          { loader: 'expose-loader', options: 'jQuery' },
          { loader: 'expose-loader', options: '$' },
        ]
      },
      {
        test: require.resolve('moment'),
        use: [
          { loader: 'expose-loader', options: 'moment' },
        ]
      }
    ]
  }
};
