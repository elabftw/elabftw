/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/* eslint-env node */

const fs = require('fs');
const peggy = require('peggy');
const phpeggy = require('phpeggy');

fs.readFile('./src/node/grammar/queryGrammar.pegjs', (err, data) => {
  if (err) {
    throw err;
  }

  // php parser
  fs.mkdir(
    './cache/advancedSearchQuery',
    { recursive: true, mode: 0777 },
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
        err => {
          if (err) {
            throw err;
          }
        },
      );
    },
  );

  // js parser
  // fs.writeFile(
    // './src/js/queryParser.js',
    // peggy.generate(data.toString(), {
      // cache: true,
      // output: 'source',
    // }),
    // err => {
      // if (err) {
        // throw err;
      // }
    // },
  // );
});
