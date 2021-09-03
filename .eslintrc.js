/*
 * Config file for eslint
 *
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Alexander Minges <alexander.minges@gmail.com>
 * @copyright 2015 Nicolas CARPi, Alexander Minges
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

module.exports = {
  'parser':  '@typescript-eslint/parser',
  'extends': 'plugin:@typescript-eslint/recommended',
  'parserOptions': {
    'ecmaVersion': 2018,
    'sourceType': 'module',
  },
  'rules': {
    'comma-dangle': ['error', 'always-multiline'],
    'indent': ['error', 2],
    'keyword-spacing': ['error', {'before': true, 'after': true}],
    'linebreak-style': ['error', 'unix'],
    'quotes': ['error', 'single'],
    'semi': ['error', 'always'],
    'space-before-function-paren': ['error', 'never'],
  }
};
