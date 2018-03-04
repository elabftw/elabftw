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
            'bootstrap/js/src/alert.js',
            'bootstrap/js/src/button.js',
            'bootstrap/js/src/collapse.js',
            'bootstrap/js/src/dropdown.js',
            //'./web/app/js/team.js',
        ],
        edit: [
            'tinymce',
//            'dropzone',
        ],
        scheduler: [
            'moment',
            //'./web/app/js/team.js',
            //'fullcalendar',
        ],
    },
    resolve: {
        alias: {
            'jquery-ui': 'jquery-ui-dist/jquery-ui.js',
            //'fullcalendar': 'fullcalendar/dist/fullcalendar.js',
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
                    //{ loader: 'expose-loader', options: 'Dropzone.options' },
                    //{ loader: 'expose-loader', options: 'fullCalendar' },
                ]
            },
            {
                test: require.resolve('moment'),
                use: [
                    { loader: 'expose-loader', options: 'moment' },
                ]
            },
            /*
            {
                test: require.resolve('fullcalendar'),
                use: [
                    { loader: 'expose-loader', options: 'fullCalendar' },
                ]
            },
            */
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
