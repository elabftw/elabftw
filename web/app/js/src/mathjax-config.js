/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
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
    },
    pageReady() {
      const options = MathJax.startup.document.options;
      const BaseMathItem = options.MathItem;
      options.MathItem = class FixedMathItem extends BaseMathItem {
        assistiveMml(document) {
          if (this.display !== null) super.assistiveMml(document);
        }
      };
      return MathJax.startup.defaultPageReady();
    }
  }
};
