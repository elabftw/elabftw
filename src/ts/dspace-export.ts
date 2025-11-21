/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 *
 * All logic related to DSpace export modal. Located in toolbar on view/edit pages
 */

import { acceptWorkspaceItemLicense, createWorkspaceItem, fetchXsrfToken, isDspaceSessionActive, listCollections,
  listTypes, loginToDspace, submitWorkspaceItemToWorkflow, updateWorkspaceItemMetadata, uploadWorkspaceItemFile
} from './dspace-utils';
import { on } from './handlers';
import i18next from './i18n';

if (document.getElementById('dspaceExportModal')) {
  on('export-to-dspace', async () => {
    const form = document.getElementById('dspaceExportForm') as HTMLFormElement;
    const collection = form.collection.value;
    const author = form.author.value;
    const title = (document.getElementById('dspaceTitle') as HTMLInputElement)?.value;
    const date = form.date.value;
    const type = form.type.value;
    const abstract = form.abstract.value;
    const file = form.file.files[0];
    // TODO: add the file directly from the experiment/resource's ELN

    const metadata = {
      metadata: [
        { key: 'dc.creator', value: author },
        { key: 'dc.title', value: title },
        { key: 'dc.date.issued', value: date },
        { key: 'dc.type', value: type },
        { key: 'dc.description.abstract', value: abstract },
      ],
    };

    try {
      const token = await fetchXsrfToken();
      // create the item in DSpace
      const item = await createWorkspaceItem(collection, metadata, token);
      const itemId = item.id;
      // accept license
      await acceptWorkspaceItemLicense(itemId, token);
      // patch required metadata to be in traditionalpageone
      await updateWorkspaceItemMetadata(itemId, token, title, date, type, abstract);
      // upload file (adds to /sections/upload)
      await uploadWorkspaceItemFile(itemId, file, token);
      // move workspaceitem to workflow (deposit)
      await submitWorkspaceItemToWorkflow(itemId, token);

      alert('Export to DSpace successful!');
    } catch (e) {
      console.error(e);
      alert(`Export failed: ${e.message}`);
    }
  });
}

$('#dspaceExportModal').on('shown.bs.modal', async () => {
  const collectionSelect = document.getElementById('dspaceCollection') as HTMLSelectElement;
  const typeSelect = document.getElementById('dspaceType') as HTMLSelectElement;
  collectionSelect.innerHTML = '<option disabled selected>' + i18next.t('loading') + '...</option>';
  typeSelect.innerHTML = '<option disabled selected>' + i18next.t('loading') + '...</option>';
  let active = await isDspaceSessionActive();
  if (!active) {
    await loginToDspace('toto@yopmail.com', 'totototototo');
  }
  try {
    const [collectionsJson, typesJson] = await Promise.all([
      listCollections(),
      listTypes()
    ]);

    const collections = collectionsJson._embedded.collections;
    const types = typesJson._embedded.entries;
    // populate collections
    collectionSelect.innerHTML = '';
    collections.forEach((col: any) => {
      const opt = document.createElement('option');
      opt.value = col.uuid;
      opt.textContent = `${col.name} (${col.uuid})`;
      collectionSelect.appendChild(opt);
    });

    // populate types
    typeSelect.innerHTML = '';
    types.forEach((type: any) => {
      const opt = document.createElement('option');
      opt.value = type.value;
      opt.textContent = type.display;
      typeSelect.appendChild(opt);
    });

  } catch (e) {
    collectionSelect.innerHTML = '<option disabled>Error loading collections</option>';
    typeSelect.innerHTML = '<option disabled>Error loading types</option>';
    console.error(e);
  }
});
