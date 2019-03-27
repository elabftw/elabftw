/*
 * Config file for eslint
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @copyright 2015 Nicolas CARPi, Alexander Minges
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

module.exports = {
    'env': {
        'browser': true,
        'es6': true
    },
    'extends': 'eslint:recommended',
    'globals': {
        'Atomics': 'readonly',
        'SharedArrayBuffer': 'readonly',
        '$': 'readonly',
        '$3Dmol': 'readonly',
        'addDateOnCursor': 'readonly',
        'ChemDoodle': 'readonly',
        'Dropzone': 'readonly',
        'getGetParameters': 'readonly',
        'google': 'readonly',
        'insertParamAndReload': 'readonly',
        'key': 'readonly',
        'makeEditableComment': 'readonly',
        'makeEditableFileComment': 'readonly',
        'makeEditableTodoitem': 'readonly',
        'MathJax': 'readonly',
        'moment': 'readonly',
        'notif': 'readonly',
        'quickSave': 'readonly',
        'relativeMoment': 'readonly',
        'saveAs': 'readonly',
        'tinymce': 'readonly'
    },
    'parserOptions': {
        'ecmaVersion': 2018,
        'sourceType': 'module'
    },
    'rules': {
        'indent': [
            'error',
            2
        ],
        'linebreak-style': [
            'error',
            'unix'
        ],
        'quotes': [
            'error',
            'single'
        ],
        'semi': [
            'error',
            'always'
        ]
    }
};
