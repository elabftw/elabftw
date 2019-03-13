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
const path = require('path');
const webpack = require('webpack');

module.exports = {
  entry: {
    main: [
      'jquery',
      'jquery-ui',
      'bootstrap/js/src/alert.js',
      'bootstrap/js/src/button.js',
      'bootstrap/js/src/collapse.js',
      'bootstrap/js/src/dropdown.js',
      './web/app/js/src/fontawesome.es.js',
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
      'prismjs/components/prism-ruby.js'
    ],
    tinymce: [
      'tinymce',
      'tinymce/themes/modern/theme',
    ],
    moment: 'moment',
  },
  plugins: [
    // only load the moment locales that we are interested in
    new webpack.ContextReplacementPlugin(/moment[\\\/]locale$/, /^\.\/(ca|de|en|es|fr|it|id|pl|pt|pt-br|ru|sk|sl|zh-cn)$/)
  ],
  resolve: {
    alias: {
      'jquery-ui': 'jquery-ui-dist/jquery-ui.js'
    }
  },
  mode: 'production',
  output: {
    filename: '[name].bundle.js',
    path: path.resolve(__dirname, 'web/app/js')
  },
  module: {
    rules:[
      // expose jquery and moment globally
      {
        test: require.resolve('jquery'),
        use: [
          { loader: 'expose-loader', options: 'jQuery' },
          { loader: 'expose-loader', options: '$' }
        ]
      },
      {
        test: require.resolve('moment'),
        use: [
          { loader: 'expose-loader', options: 'moment' }
        ]
      }
    ]
  }
};
