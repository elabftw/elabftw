/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { Ketcher } from 'ketcher-core';
import $ from 'jquery';
import { notify } from './notify';
import {on} from './handlers';

// we add ketcher to window with onInit param during ketcher initialization
declare global {
  interface Window {
    ketcher: Ketcher;
  }
}

if (window.location.pathname === '/chem-editor.php') {
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
      // reload the table
      document.dispatchEvent(new CustomEvent('dataReload'));
    });
  });
  on('create-compound-from-editor', async () => {
    const smilesInput = document.getElementById('createCompound-smiles') as HTMLInputElement;
    smilesInput.value = await window.ketcher.getSmiles();
    const inchiInput = document.getElementById('createCompound-inchi') as HTMLInputElement;
    inchiInput.value = await window.ketcher.getInchi();
    $('#createCompoundModal').modal('toggle');
  });
  document.getElementById('loading-spinner').remove();
}
