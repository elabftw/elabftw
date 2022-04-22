/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/* eslint-env node */

const fs = require('fs');
const peggy = require('peggy');
const phpeggy = require('phpeggy');

fs.readFile('./src/node/grammar/queryGrammar.pegjs', 'utf8', (err, data) => {
  if (err) {
    throw err;
  }

  fs.mkdir(
    './cache/advancedSearchQuery',
    {recursive: true, mode: 0755},
    err => {
      if (err) {
        throw err;
      }

      fs.writeFile(
        './cache/advancedSearchQuery/Parser.php',
        peggy.generate(data.toString(), {
          cache: true,
          plugins: [phpeggy],
          phpeggy: {
            parserNamespace: 'Elabftw\\Services\\AdvancedSearchQuery\\Grammar',
            parserClassName: 'Parser',
          },
        }),
        {mode: 0644},
        err => {
          if (err) {
            throw err;
          }
        },
      );
    },
  );
});
