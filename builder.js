/*
 * Config file for webpack
 *
 * This is in fact webpack.config.js but I renamed it builder.js
 * because I don't want any path clash with the web folder when
 * doing autocompletion.
 */
const path = require('path');
//const webpack = require('webpack');

module.exports = {
    entry: {
        main: [
            'jquery',
            'jquery-ui',
            'bootstrap/js/alert.js',
            'bootstrap/js/button.js',
            'bootstrap/js/collapse.js',
            'bootstrap/js/dropdown.js',
        ],
        edit: [
            'tinymce',
//            'dropzone',
        ],
    },
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
            // Expose jquery globally for inline/legacy use
            {
                test: require.resolve('jquery'),
                use: [
                    { loader: 'expose-loader', options: 'jQuery' },
                    { loader: 'expose-loader', options: '$' },
                    { loader: 'expose-loader', options: 'Dropzone.options' },
                ]
            },
        ],
    },
    /*
    plugins: [
        new webpack.ProvidePlugin({
            $: "jquery",
            jQuery: "jquery",
        })
    ]
    */
};
