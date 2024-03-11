/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/*
 * Based on https://github.com/mathjax/MathJax-demos-web/blob/master/custom-component/custom-component.js
 * but modified to match 'mathjax-full/components/src/tex-svg-full/tex-svg-full.js
 * and with lazy typesetting (new in version 3.2.0, see https://www.mathjax.org/MathJax-v3.2.0-available/#lazy)
 * and the custom config used by eLabFTW (previously in src/js/mathjax-config.js)
*/

//  Initialize the MathJax startup code
import 'mathjax-full/components/src/startup/lib/startup.js';

//  Get the loader module and indicate the modules that
//  will be loaded by hand below
import { Loader } from 'mathjax-full/js/components/loader.js';
Loader.preLoad(
  'loader',
  'startup',
  'core',
  'input/tex-full',
  'input/mml',
  'output/svg',
  'output/svg/fonts/tex.js',
  'ui/menu',
  'ui/lazy',
  'a11y/assistive-mml',
);

import { MathJaxObject } from 'mathjax-full/js/components/startup';
declare const MathJax: MathJaxObject;

// Now insert the config
import { insert } from 'mathjax-full/js/util/Options.js';
insert(
  MathJax.config,
  {
    tex: {
      inlineMath: [ ['$','$'], ['\\(','\\)'] ],
      displayMath: [ ['$$','$$'], ['\\[','\\]'] ],
      processEscapes: true,
      tags: 'ams',
      packages: ['base', 'ams', 'autoload'],
    },
    options: {
      ignoreHtmlClass: 'mathjax-ignore',
    },
    startup: {
      pageReady(): Promise<void> {
        const options = MathJax.startup.document.options;
        const BaseMathItem = options.MathItem;
        options.MathItem = class FixedMathItem extends BaseMathItem {
          assistiveMml(document): void {
            if (this.display !== null) {
              super.assistiveMml(document);
            }
          }
        };
        return MathJax.startup.defaultPageReady();
      },
    },
  },
  false,
);

// Load the components that we want to use
// (the ones listed in the preLoad() call above)
import 'mathjax-full/components/src/core/core.js';

import 'mathjax-full/components/src/input/tex-full/tex-full.js';
import 'mathjax-full/components/src/input/mml/mml.js';

import 'mathjax-full/components/src/output/svg/svg.js';
import 'mathjax-full/components/src/output/svg/fonts/tex/tex.js';

import 'mathjax-full/components/src/ui/menu/menu.js';
import 'mathjax-full/components/src/ui/lazy/lazy.js';

import 'mathjax-full/components/src/a11y/assistive-mml/assistive-mml.js';

// Loading this component will cause all the normal startup
// operations to be performed when this component is loaded
import 'mathjax-full/components/src/startup/startup.js';
