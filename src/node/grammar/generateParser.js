/* eslint-env node */

const fs = require('fs');
const pegjs = require('pegjs');
const phpegjs = require('phpegjs');

fs.readFile('./src/node/grammar/queryGrammar.pegjs', (err, data) => {
  if (err) {
    throw err;
  }

  // php parser
  fs.writeFile(
    './src/services/advancedSearchQuery/grammar/Parser.php',
    pegjs.generate(data.toString(), {
      cache: true,
      plugins: [phpegjs],
      phppegjs: {
        parserNamespace: 'Elabftw\\Services\\AdvancedSearchQuery\\Grammar',
        parserClassName: 'Parser',
        //typeHint: true,
        //stricTypes: true,
      },
    }),
    err => {
      if (err) {
        throw err;
      }
    },
  );

  // js parser
  // fs.writeFile(
    // './src/js/queryParser.js',
    // pegjs.generate(data.toString(), {
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
