/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { createRoot } from 'react-dom/client'
import KetcherEditor from './ketcher';
import {notifError} from './misc';

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('ketcher-root')) {
    const root = createRoot(document.getElementById('ketcher-root'));
    // Note: use <StrictMode> in dev to spot errors
    root.render(
      <KetcherEditor />
    );
    document.getElementById('ketcher-actions').addEventListener('click', async (event) => {
      const el = event.target;
      if (el.matches('[data-action="search-from-editor"]')) {
        window.ketcher.getSmiles().then(s => {
          if (!s) {
            notifError(new Error('No structure found!'));
            return;
          }
          const smilesInput = document.getElementById('substructureSearchInput');
          smilesInput.value = s;
          const resultsParentDiv = document.getElementById('searchFpResultsDiv');
          resultsParentDiv.removeAttribute('hidden');
          // reload the table
          document.dispatchEvent(new CustomEvent('dataReload'));
        });
      }
    });
  }
});
