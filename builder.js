/**
 * builder.js
 *
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
            './web/app/js/src/fontawesome.es.js'
        ],
        tinymce: [
            'tinymce'
        ],
        moment: [
            'moment'
        ]
    },
    plugins: [
        // only load the moment locales that we are interested in
        new webpack.ContextReplacementPlugin(/moment[\\\/]locale$/, /^\.\/(ca|de|en|es|fr|it|pl|pt|pt-br|ru|sk|sl|zh-cn)$/)
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
