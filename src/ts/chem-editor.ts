/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { Ketcher } from 'ketcher-core';
import './ketcher-editor';
import { showModal } from './common';
import { notify } from './notify';
import { on } from './handlers';

// we add ketcher to window with onInit param during ketcher initialization
declare global {
  interface Window {
    ketcher: Ketcher;
  }
}

export { loadAttachmentFromList } from './chem-attachments';

// the results table is mounted on the first search only: mounted, it reads the compounds at once
let compoundsTable: Promise<unknown> | null = null;

on('search-from-editor', () => {
  window.ketcher.getSmiles().then(s => {
    if (!s) {
      notify.error('not-found');
      return;
    }
    const smilesInput = document.getElementById('substructureSearchInput') as HTMLInputElement;
    smilesInput.value = s;
    const resultsParentDiv = document.getElementById('searchFpResultsDiv');
    resultsParentDiv.removeAttribute('hidden');
    if (compoundsTable) {
      // reload the table
      document.dispatchEvent(new CustomEvent('dataReload'));
      return;
    }
    // compounds-table.jsx mounts in this element, and searches with the structure set above
    document.getElementById('chemCompoundsTable').id = 'compounds-table';
    compoundsTable = import(
      /* webpackChunkName: 'compounds-table' */
      './compounds-table'
    );
  });
});
on('create-compound-from-editor', async () => {
  try {
    const smilesInput = document.getElementById('createCompound-smiles') as HTMLInputElement;
    const inchiInput = document.getElementById('createCompound-inchi') as HTMLInputElement;
    // not in parallel: both are conversions of the same structure, and the standalone struct service
    // would give the first answer to both, see componentFromEntry() in reaction.ts
    const smiles = await window.ketcher.getSmiles();
    const inchi = await window.ketcher.getInchi();
    smilesInput.value = smiles;
    inchiInput.value = inchi;
    // the button that triggers this is outside of the modal, so it is never already shown
    showModal('#createCompoundModal');
  } catch (error) {
    notify.error('Failed to get molecular data from editor');
    console.error('Ketcher error:', error);
  }
});
