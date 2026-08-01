/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 * @see https://github.com/mathjax/MathJax-demos-node/blob/060220686e5e368d9b654169eb4199b1f3de5a96/preload/tex2svg-page
 *
 * Use MathJax v3 to convert all TeX in an HTML document.
 */


// Read the HTML file passed as the first argument
const htmlfile = require('fs').readFileSync(process.argv[2], 'utf8');

// Configure MathJax
globalThis.MathJax = {
    tex: {
        inlineMath: [['$','$'], ['\\(','\\)']],
        displayMath: [['$$','$$'], ['\\[','\\]']],
        processEscapes: true,
        packages: ['base', 'ams', 'autoload'],
        // fail hard
        formatError: (jax, err) => {throw err},
    },
    svg: {
        fontCache: 'local',
    },
    startup: {
        document: htmlfile,
    },
    options: {
        // fail hard
        compileError: (doc, math, err) => {throw err},
        typesetError: (doc, math, err) => {throw err},
    }
};

try {
    const {MathJax} = require('@mathjax/src/js/components/global.js');
    const {Loader} = require('@mathjax/src/js/components/loader.js');
    const {insert} = require('@mathjax/src/js/util/Options.js');
    const {MathJaxTexFont} = require('@mathjax/mathjax-tex-font/js/svg.js');

    require('@mathjax/src/js/components/startup.js');
    require('@mathjax/src/components/js/core/core.js');
    require('@mathjax/src/components/js/adaptors/liteDOM/liteDOM.js');
    require('@mathjax/src/components/js/input/tex/tex.js');
    require('@mathjax/src/components/js/input/mml/entities/entities.js');
    require('@mathjax/src/components/js/output/svg/svg.js');

    // Bundle every extension reachable from the default autoload config.
    require('@mathjax/src/components/js/input/tex/extensions/action/action.js');
    require('@mathjax/src/components/js/input/tex/extensions/amscd/amscd.js');
    require('@mathjax/src/components/js/input/tex/extensions/bbox/bbox.js');
    require('@mathjax/src/components/js/input/tex/extensions/boldsymbol/boldsymbol.js');
    require('@mathjax/src/components/js/input/tex/extensions/braket/braket.js');
    require('@mathjax/src/components/js/input/tex/extensions/bussproofs/bussproofs.js');
    require('@mathjax/src/components/js/input/tex/extensions/cancel/cancel.js');
    require('@mathjax/src/components/js/input/tex/extensions/color/color.js');
    require('@mathjax/src/components/js/input/tex/extensions/enclose/enclose.js');
    require('@mathjax/src/components/js/input/tex/extensions/extpfeil/extpfeil.js');
    require('@mathjax/src/components/js/input/tex/extensions/html/html.js');
    require('@mathjax/src/components/js/input/tex/extensions/mhchem/mhchem.js');
    require('@mathjax/src/components/js/input/tex/extensions/unicode/unicode.js');
    require('@mathjax/src/components/js/input/tex/extensions/verb/verb.js');

    Loader.preLoaded(
        'core',
        'adaptors/liteDOM',
        'input/tex',
        'input/mml/entities',
        'output/svg',
        ...[
            'action', 'amscd', 'bbox', 'boldsymbol', 'braket', 'bussproofs',
            'cancel', 'color', 'enclose', 'extpfeil', 'html', 'mhchem',
            'unicode', 'verb',
        ].map(name => `[tex]/${name}`),
    );

    insert(MathJax.config, {
        svg: {
            // The v3 TeX font is complete, so rendering stays synchronous.
            fontData: MathJaxTexFont,
        },
    }, false);

    MathJax.config.startup.ready();

    const adaptor = MathJax.startup.adaptor;
    const html = MathJax.startup.document;

    // If math was found, output the resulting HTML.
    if (Array.from(html.math).length > 0) {
        console.log(adaptor.doctype(html.document));
        console.log(adaptor.outerHTML(adaptor.root(html.document)));
    }
} catch (err) {
    console.error('MathJax Error:', err.message);
    process.exitCode = 1;
}
