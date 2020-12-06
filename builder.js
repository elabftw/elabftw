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

module.exports = {
  entry: {
    main: [
      './src/ts/common.ts',
      './src/ts/i18n.ts',
      './src/ts/steps-links.ts',
      './src/ts/tabs.ts',
      './src/ts/tags.ts',
      './src/ts/admin.ts',
      './src/ts/edit.ts',
      './src/ts/jsoneditor.ts',
      './src/ts/team.ts',
      './src/ts/uploads.ts',
      './src/ts/todolist.ts',
      './src/ts/ucp.ts',
      './src/ts/view.ts',
      './src/ts/comments.ts',
      './src/ts/editusers.ts',
      './src/ts/profile.ts',
      './src/ts/search.ts',
      './src/ts/show.ts',
      './src/ts/sysconfig.ts',
      './src/ts/change-pass.ts',
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
  },
  // uncomment this to find where the error is coming from
  // makes the build slower
  //devtool: 'inline-source-map',
  plugins: [
    // only load the moment locales that we are interested in
    new webpack.ContextReplacementPlugin(/moment[\\\/]locale$/, /^\.\/(ca|de|en|es|fr|it|id|ja|kr|nl|pl|pt|pt-br|ru|sk|sl|zh-cn)$/),
  ],
  mode: 'production',
  output: {
    filename: '[name].bundle.js',
    path: path.resolve(__dirname, 'web/app/js')
  },
  optimization: {
    splitChunks: {
      chunks: 'all',
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
        use: 'css-loader',
      },
      // transpile things with babel so javascript works with Edge
      {
        test: /\.m?js$/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env'],
            compact: false,
          }
        }
      },
      // expose jquery and moment globally
      {
        test: require.resolve('jquery'),
        loader: 'expose-loader',
        options: {
          exposes: ['$', 'jQuery'],
        },
      },
      {
        test: require.resolve('moment'),
        loader: 'expose-loader',
          options: {
            exposes: 'moment',
          },
      },
      // use a custom loader for 3Dmol.js
      {
        test: /3Dmol-nojquery.js$/,
        use: {
          loader: path.resolve('src/ts/3Dmol-loader.js'),
        },
      }
    ]
  }
};
