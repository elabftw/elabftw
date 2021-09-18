/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

// based on 'mathjax-full/es5/tex-svg-full.js' (source at 'mathjax-full/components/src/tex-svg-full/tex-svg-full.js)
// but with lazy typesetting (new in version 3.2.0)
// see https://www.mathjax.org/MathJax-v3.2.0-available/#lazy

import 'mathjax-full/components/src/startup/lib/startup.js';
import {Loader} from 'mathjax-full/js/components/loader.js';

Loader.preLoad(
  'loader', 'startup',
  'core',
  'input/tex-full',
  'output/svg', 'output/svg/fonts/tex.js',
  'ui/menu', 'ui/lazy',
  'a11y/assistive-mml',
);

import 'mathjax-full/components/src/core/core.js';
import 'mathjax-full/components/src/input/tex-full/tex-full.js';
import 'mathjax-full/components/src/output/svg/svg.js';
import 'mathjax-full/components/src/output/svg/fonts/tex/tex.js';
import 'mathjax-full/components/src/ui/menu/menu.js';
import 'mathjax-full/components/src/ui/lazy/lazy.js';
import 'mathjax-full/components/src/a11y/assistive-mml/assistive-mml.js';
import 'mathjax-full/components/src/startup/startup.js';
