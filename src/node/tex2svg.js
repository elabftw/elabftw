/*************************************************************************
 *
 *  based on https://github.com/mathjax/MathJax-demos-node/blob/060220686e5e368d9b654169eb4199b1f3de5a96/preload/tex2svg-page
 *
 *  Uses MathJax v3 to convert all TeX in an HTML document.
 *
 */


//
//  Read the HTML file
//  must be the first argument
//
const htmlfile = require('fs').readFileSync(process.argv[2], 'utf8');

//
// Configure MathJax
//
MathJax = {
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

//
//  Load all the needed components
//
require('mathjax-full/components/src/startup/lib/startup.js');
require('mathjax-full/components/src/core/core.js');
require('mathjax-full/components/src/adaptors/liteDOM/liteDOM.js');
require('mathjax-full/components/src/input/tex-full/tex-full.js');
require('mathjax-full/components/src/input/tex/extensions/all-packages/all-packages.js');
require('mathjax-full/components/src/output/svg/svg.js');
require('mathjax-full/components/src/output/svg/fonts/tex/tex.js');
require('mathjax-full/components/src/startup/startup.js');

//
//  Let MathJax know these are loaded
//
MathJax.loader.preLoad(
    'core',
    'adaptors/liteDOM',
    'input/tex-full',
    '[tex]/all-packages',
    'output/svg',
    'output/svg/fonts/tex',
);

//
//  Create the MathJax methods for the input and output that is loaded
//
MathJax.config.startup.ready();

//
//  Wait for MathJax to start up, and then typeset the math
//
MathJax.startup.promise.then(() => {
    const adaptor = MathJax.startup.adaptor;
    const html = MathJax.startup.document;

    //
    //  If math was found output the resulting HTML
    //
    if (Array.from(html.math).length > 0) {
        console.log(adaptor.doctype(html.document));
        console.log(adaptor.outerHTML(adaptor.root(html.document)));
    }
}).catch(err => {
    //
    // Print error and fail hard
    //
    console.error('MathJax Error:', err.message);
    process.exitCode = 1;
});
