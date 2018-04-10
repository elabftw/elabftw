/*
 * Config file for webpack
 *
 * This is in fact webpack.config.js but I renamed it builder.js
 * because I don't want any path clash with the web folder when
 * doing autocompletion.
 */
const path = require('path');
const webpack = require('webpack');
//const HtmlWebpackPlugin = require('html-webpack-plugin');

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
            'keypress.js'
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
        new webpack.ContextReplacementPlugin(/moment[\\\/]locale$/, /^\.\/(ca|de|en|es|fr|it|pl|pt|pt-br|ru|sl|zh-cn)$/)
    /*
        new HtmlWebpackPlugin({
            //filename: path.resolve(__dirname, 'web/app/tpl/head.html'),
            filename: 'scripts.html',
            //inject: 'head',
            minify: false,
            //template: 'web/app/tpl/head.html'
        })
        */
    ],
    resolve: {
        alias: {
            'jquery-ui': 'jquery-ui-dist/jquery-ui.js',
            '@fortawesome/fontawesome-free-solid$': '@fortawesome/fontawesome-free-solid/shakable.es.js',
            '@fortawesome/fontawesome-free-regular$': '@fortawesome/fontawesome-free-regular/shakable.es.js',
            '@fortawesome/fontawesome-free-brands$': '@fortawesome/fontawesome-free-brands/shakable.es.js'
        }
    },
    mode: 'production',
    output: {
        //filename: '[name].[hash].bundle.js',
        filename: '[name].bundle.js',
        path: path.resolve(__dirname, 'web/app/js')
    },
    module: {
        rules:[
            // Expose jquery globally for inline/legacy use
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
            },
            {
                test: require.resolve('keypress.js'),
                use: [
                    { loader: 'expose-loader', options: 'keypress' }
                ]
            }
        ]
    }
};
