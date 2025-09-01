/**
 * @author Nicolas CARPi @ Deltablot
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare global {
  interface i3dmol {
    // https://github.com/3dmol/3Dmol.js/blob/master/src/autoload.ts
    // upstream is not typed, let's just go with ani and call it a day.
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    autoload: any;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    viewers: any;
  }
}

export async function displayMoleculeViewer(): Promise<void> {
  // check first if there are elements to render
  const elements = document.getElementsByClassName('viewer_3Dmoljs');
  if (elements.length < 1) {
    return;
  }
  // now dynamically load the lib and use autoload function
  // Note: using createViewer() function  on individual elements is tricky
  // because of the reload aspect of uploadsDiv and the canvas getting killed
  // and the renderer becoming like this meme from pulp fiction
  get3dmol().then(($3Dmol) => $3Dmol.autoload());
}

export async function get3dmol(): Promise<i3dmol>
{
  return import('3dmol');
}
