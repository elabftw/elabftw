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
import { on } from './handlers';

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
    try {
      const smilesInput = document.getElementById('createCompound-smiles') as HTMLInputElement;
      const inchiInput = document.getElementById('createCompound-inchi') as HTMLInputElement;
      const [smiles, inchi] = await Promise.all([
        window.ketcher.getSmiles(),
        window.ketcher.getInchi(),
      ]);
      smilesInput.value = smiles;
      inchiInput.value = inchi;
      $('#createCompoundModal').modal('toggle');
    } catch (error) {
      notify.error('Failed to get molecular data from editor');
      console.error('Ketcher error:', error);
    }
  });
  document.getElementById('loading-spinner').remove();
}
