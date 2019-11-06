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
    packages: ['base', 'ams', 'autoload']
  },
  startup: {
    ready: () => {
      MathJax.startup.defaultReady();
    }
  }
};
