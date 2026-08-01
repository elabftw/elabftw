/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 * @see https://github.com/mathjax/MathJax-demos-node/blob/060220686e5e368d9b654169eb4199b1f3de5a96/preload/tex2svg-page
 *
 * Use MathJax v4 to convert all TeX in an HTML document.
 */

const fs = require('fs');
const path = require('path');

const {mathjax} = require('@mathjax/src/js/mathjax.js');
const {TeX} = require('@mathjax/src/js/input/tex.js');
const {SVG} = require('@mathjax/src/js/output/svg.js');
const {liteAdaptor} = require('@mathjax/src/js/adaptors/liteAdaptor.js');
const {RegisterHTMLHandler} = require('@mathjax/src/js/handlers/html.js');
const {MathJaxNewcmFont} = require(
  '@mathjax/mathjax-newcm-font/js/svg.js',
);
const {MathJaxMhchemFontExtension} = require(
  '@mathjax/mathjax-mhchem-font-extension/js/svg.js',
);

require('@mathjax/src/js/util/entities/all.js');
require('@mathjax/src/js/input/tex/base/BaseConfiguration.js');
require('@mathjax/src/js/input/tex/ams/AmsConfiguration.js');
require('@mathjax/src/js/input/tex/mhchem/MhchemConfiguration.js');
require('@mathjax/src/js/input/tex/action/ActionConfiguration.js');
require('@mathjax/src/js/input/tex/amscd/AmsCdConfiguration.js');
require('@mathjax/src/js/input/tex/bbox/BboxConfiguration.js');
require('@mathjax/src/js/input/tex/boldsymbol/BoldsymbolConfiguration.js');
require('@mathjax/src/js/input/tex/braket/BraketConfiguration.js');
require('@mathjax/src/js/input/tex/bussproofs/BussproofsConfiguration.js');
require('@mathjax/src/js/input/tex/cancel/CancelConfiguration.js');
require('@mathjax/src/js/input/tex/color/ColorConfiguration.js');
require('@mathjax/src/js/input/tex/enclose/EncloseConfiguration.js');
require('@mathjax/src/js/input/tex/extpfeil/ExtpfeilConfiguration.js');
require('@mathjax/src/js/input/tex/html/HtmlConfiguration.js');
require('@mathjax/src/js/input/tex/mhchem/MhchemConfiguration.js');
require('@mathjax/src/js/input/tex/newcommand/NewcommandConfiguration.js');
require('@mathjax/src/js/input/tex/unicode/UnicodeConfiguration.js');
require('@mathjax/src/js/input/tex/verb/VerbConfiguration.js');

// Keep aligned with the browser's default autoload targets in mathjax.ts.
const texPackages = [
  'base',
  'ams',
  'action',
  'amscd',
  'bbox',
  'boldsymbol',
  'braket',
  'bussproofs',
  'cancel',
  'color',
  'enclose',
  'extpfeil',
  'html',
  'mhchem',
  'newcommand',
  'unicode',
  'verb',
];

const newcmDynamic = require.context(
  'mathjax-newcm-svg-dynamic',
  false,
  /\.js$/,
);

const newcmDynamicFiles = new Set(newcmDynamic.keys());

mathjax.asyncLoad = name => {
  const request = `./${path.basename(name)}`;

  if (!newcmDynamicFiles.has(request)) {
    throw new Error(`Unsupported MathJax dynamic module: ${name}`);
  }

  return newcmDynamic(request);
};

const htmlfile = fs.readFileSync(process.argv[2], 'utf8');
const adaptor = liteAdaptor({fontSize: 16});

RegisterHTMLHandler(adaptor);

const tex = new TeX({
  inlineMath: [['$', '$'], ['\\(', '\\)']],
  displayMath: [['$$', '$$'], ['\\[', '\\]']],
  processEscapes: true,
  packages: texPackages,
  formatError: (_jax, error) => {
    throw error;
  },
});

MathJaxNewcmFont.addExtension(MathJaxMhchemFontExtension);

const svg = new SVG({
  fontCache: 'local',
  fontData: MathJaxNewcmFont,
  exFactor: 0.5,
});

const html = mathjax.document(htmlfile, {
  InputJax: tex,
  OutputJax: svg,
  compileError: (_document, _math, error) => {
    throw error;
  },
  typesetError: (_document, _math, error) => {
    throw error;
  },
});

async function main() {
  await html.renderPromise();

  // Preserve the existing contract: no stdout means no math was found.
  if (Array.from(html.math).length === 0) {
    return;
  }

  process.stdout.write(adaptor.doctype(html.document));
  process.stdout.write('\n');
  process.stdout.write(adaptor.outerHTML(adaptor.root(html.document)));
  process.stdout.write('\n');
}

main().catch(error => {
  console.error('MathJax Error:', error.message);
  process.exitCode = 1;
});
