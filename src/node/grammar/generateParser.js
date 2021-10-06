/* eslint-env node */

const fs = require('fs');
const pegjs = require('pegjs');
const phpegjs = require('phpegjs');

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
        pegjs.generate(data.toString(), {
          cache: true,
          plugins: [phpegjs],
          phppegjs: {
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
