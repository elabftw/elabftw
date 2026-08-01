/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { MathJax } from '@mathjax/src/js/components/global.js';
import { Loader } from '@mathjax/src/js/components/loader.js';
import { insert } from '@mathjax/src/js/util/Options.js';
import { startup } from '@mathjax/src/components/js/startup/init.js';

import '@mathjax/src/components/js/core/core.js';
import '@mathjax/src/components/js/input/tex/tex.js';
import '@mathjax/src/components/js/input/mml/mml.js';
import {
  loadFont,
} from '@mathjax/src/components/js/output/svg/svg.js';
import {
  MathJaxNewcmFont,
} from '@mathjax/mathjax-newcm-font/js/svg.js';
import {
  MathJaxMhchemFontExtension,
} from '@mathjax/mathjax-mhchem-font-extension/js/svg.js';
import '@mathjax/src/components/js/ui/menu/menu.js';
import '@mathjax/src/components/js/ui/lazy/lazy.js';
import '@mathjax/src/components/js/a11y/assistive-mml/assistive-mml.js';

// Components reachable from the default autoload configuration.
import '@mathjax/src/components/js/input/tex/extensions/action/action.js';
import '@mathjax/src/components/js/input/tex/extensions/amscd/amscd.js';
import '@mathjax/src/components/js/input/tex/extensions/bbox/bbox.js';
import '@mathjax/src/components/js/input/tex/extensions/boldsymbol/boldsymbol.js';
import '@mathjax/src/components/js/input/tex/extensions/braket/braket.js';
import '@mathjax/src/components/js/input/tex/extensions/bussproofs/bussproofs.js';
import '@mathjax/src/components/js/input/tex/extensions/cancel/cancel.js';
import '@mathjax/src/components/js/input/tex/extensions/color/color.js';
import '@mathjax/src/components/js/input/tex/extensions/enclose/enclose.js';
import '@mathjax/src/components/js/input/tex/extensions/extpfeil/extpfeil.js';
import '@mathjax/src/components/js/input/tex/extensions/html/html.js';
import '@mathjax/src/components/js/input/tex/extensions/mhchem/mhchem.js';
import '@mathjax/src/components/js/input/tex/extensions/unicode/unicode.js';
import '@mathjax/src/components/js/input/tex/extensions/verb/verb.js';

// The mhchem TeX component declares a matching SVG font extension.
// Bundle and register it here so MathJax never tries to fetch it at runtime.
MathJaxNewcmFont.addExtension(MathJaxMhchemFontExtension);

Loader.preLoaded(
  'core',
  'input/tex',
  'input/mml',
  'output/svg',
  'ui/menu',
  'ui/lazy',
  'a11y/assistive-mml',
  '[mathjax-mhchem-extension]/svg',
  ...[
    'action', 'amscd', 'bbox', 'boldsymbol', 'braket', 'bussproofs',
    'cancel', 'color', 'enclose', 'extpfeil', 'html', 'mhchem', 'unicode',
    'verb',
  ].map(name => `[tex]/${name}`),
);

insert(
  MathJax.config,
  {
    output: {
      font: 'mathjax-newcm',
    },
    tex: {
      inlineMath: [ ['$','$'], ['\\(','\\)'] ],
      displayMath: [ ['$$','$$'], ['\\[','\\]'] ],
      processEscapes: true,
      tags: 'ams',
      packages: ['base', 'ams', 'autoload'],
    },
    options: {
      ignoreHtmlClass: 'mathjax-ignore',
      // Match v3: keep assistive MathML without loading the v4 speech worker.
      menuOptions: {
        settings: {
          enrich: false,
          speech: false,
          braille: false,
        },
      },

    },
  },
  false,
);

// output/svg/svg.js registered this path before insert() ran.
MathJax.config.loader.paths['mathjax-newcm'] =
  '/assets/mathjax/mathjax-newcm-font';

loadFont(startup, true);
