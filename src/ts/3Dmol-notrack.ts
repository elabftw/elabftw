/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

// make this file a module to avoid global scope augmentation error
export {};

declare global {
  interface Window {
    $3Dmol: any; // eslint-disable-line @typescript-eslint/no-explicit-any
  }
}

// set switch: see https://github.com/3dmol/3Dmol.js/issues/337#issuecomment-326113048
window.$3Dmol = {notrack: true};
