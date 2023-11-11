/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/* eslint-env node */

const phpeggy = require('phpeggy');

module.exports = {
  cache: true,
  plugins: [phpeggy],
  phpeggy: {
    parserNamespace: 'Elabftw\\Services\\AdvancedSearchQuery\\Grammar',
    parserClassName: 'Parser',
  },
};
