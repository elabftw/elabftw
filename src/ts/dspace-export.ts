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

import {
  acceptWorkspaceItemLicense,
  createWorkspaceItem,
  DspaceCollectionList, DspaceVocabularyEntryList,
  fetchXsrfToken,
  getItemUuidFromDspace,
  isDspaceSessionActive,
  listCollections,
  listTypes,
  loginToDspace,
  submitWorkspaceItemToWorkflow,
  updateWorkspaceItemMetadata,
  uploadWorkspaceItemFile,
} from './dspace-utils';
import { on } from './handlers';
import i18next from './i18n';
import { notify } from './notify';

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
    // in dspace, license is a default.license file: there's only one. Only possible action is accept: yes or no -> checkbox
    const licenseAccepted = form.querySelector<HTMLInputElement>('#dspaceLicense')!.checked;
    if (!licenseAccepted) {
      alert(i18next.t('license-error'));
      return;
    }

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
      // create the item's WORKSPACE in DSpace
      const workspace = await createWorkspaceItem(collection, metadata, token);
      const workspaceId = workspace.id;
      // get real DSpace item UUID to store be stored in elab)
      const itemUuid = await getItemUuidFromDspace(workspaceId, token);
      console.log('DSpace item UUID:', itemUuid);
      // accept license (only reached if checkbox was checked)
      await acceptWorkspaceItemLicense(workspaceId, token);
      // metadata section
      await updateWorkspaceItemMetadata(workspaceId, token, title, date, type, abstract);
      // mandatory file upload
      await uploadWorkspaceItemFile(workspaceId, file, token);
      // submit (deposit) to workflow. Catch here if the POST is not sent, otherwise the response time being >120sec we don't await it.
      submitWorkspaceItemToWorkflow(workspaceId, token).catch(() => notify.error('submission-error'));
      notify.success('export-success');
    } catch (e) {
      notify.error('submission-error');
      console.error(e);
    }
  });
}

$('#dspaceExportModal').on('shown.bs.modal', async () => {
  const collectionSelect = document.getElementById('dspaceCollection') as HTMLSelectElement;
  const typeSelect = document.getElementById('dspaceType') as HTMLSelectElement;
  collectionSelect.innerHTML = '<option disabled selected>' + i18next.t('loading') + '...</option>';
  typeSelect.innerHTML = '<option disabled selected>' + i18next.t('loading') + '...</option>';
  const active = await isDspaceSessionActive();
  if (!active) {
    await loginToDspace('toto@yopmail.com', 'totototototo');
  }
  try {
    const [collectionsJson, typesJson] = await Promise.all([
      listCollections(),
      listTypes(),
    ]);

    const collections = collectionsJson._embedded.collections;
    const types = typesJson._embedded.entries;
    // populate collections
    collectionSelect.innerHTML = '';
    collections.forEach((col: DspaceCollectionList) => {
      const opt = document.createElement('option');
      opt.value = col.uuid;
      opt.textContent = `${col.name} (${col.uuid})`;
      collectionSelect.appendChild(opt);
    });

    // populate types
    typeSelect.innerHTML = '';
    types.forEach((type: DspaceVocabularyEntryList) => {
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
