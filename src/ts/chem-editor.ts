/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import { notifError } from './misc';
import { Ketcher } from '@deltablot/ketcher-core';
import $ from 'jquery';

// we add ketcher to window with onInit param during ketcher initialization
declare global {
  interface Window {
    ketcher: Ketcher;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/chem-editor.php') {
    return;
  }
  document.getElementById('ketcher-actions').addEventListener('click', async (event) => {
    const el = (event.target as HTMLElement);
    if (el.matches('[data-action="search-from-editor"]')) {
      window.ketcher.getSmiles().then(s => {
        if (!s) {
          notifError(new Error('No structure found!'));
          return;
        }
        const smilesInput = document.getElementById('substructureSearchInput') as HTMLInputElement;
        smilesInput.value = s;
        const resultsParentDiv = document.getElementById('searchFpResultsDiv');
        resultsParentDiv.removeAttribute('hidden');
        // reload the table
        document.dispatchEvent(new CustomEvent('dataReload'));
      });
    } else if (el.matches('[data-action="create-compound-from-editor"]')) {
      const smilesInput = document.getElementById('createCompound-smiles') as HTMLInputElement;
      smilesInput.value = await window.ketcher.getSmiles();
      const inchiInput = document.getElementById('createCompound-inchi') as HTMLInputElement;
      inchiInput.value = await window.ketcher.getInchi();
      $('#createCompoundModal').modal('toggle');
    }
  });
  document.getElementById('loading-spinner').remove();
});
