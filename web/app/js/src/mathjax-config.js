/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
window.MathJax = {
  tex: {
    inlineMath: [ ['$','$'], ['\\(','\\)'] ],
    displayMath: [ ['$$','$$'], ['\\[','\\]'] ],
    processEscapes: true,
    packages: ['base', 'autoload']
  },
   startup: {
         ready: () => {
               console.log('MathJax is loaded, but not yet initialized');
                     MathJax.startup.defaultReady();
                           console.log('MathJax is initialized, and the initial typeset is queued');
                               }
                                 }
  /*
  options: {
    ignoreHtmlClass: 'tex2jax_ignore',
    processHtmlClass: 'tex2jax_process'
  }
  */
};
/* old
window.MathJax = {
  extensions: ['tex2jax.js'],
  jax: ['input/TeX', 'output/HTML-CSS'],
  tex2jax: {
    inlineMath: [ ['$','$'], ['\\(','\\)'] ],
    displayMath: [ ['$$','$$'], ['\\[','\\]'] ],
    processEscapes: true
  },
  'HTML-CSS': {
    fonts: ['TeX']
  },
  TeX: {
    extensions: ['autoload-all.js']
  }
};
*/
