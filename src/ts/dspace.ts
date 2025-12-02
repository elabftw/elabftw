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

import { ApiC } from "./api";
import {
  acceptWorkspaceItemLicense, buildCurrentEntryEln,
  createWorkspaceItem, DspaceCollection,
  DspaceVocabularyEntry,
  // ensureDspaceAuthFromBackend,
  // fetchXsrfToken,
  getItemUuidFromDspace,
  listCollections,
  listTypes,
  saveDspaceIdAsExtraField,
  submitWorkspaceItemToWorkflow,
  updateWorkspaceItemMetadata,
  uploadWorkspaceItemFile,
} from './dspaceUtils';
import { on } from './handlers';
import i18next from './i18n';
import { Method } from "./interfaces";
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
    // in dspace, license is a default.license file: there's only one. Only possible action is accept: yes or no -> checkbox
    const licenseAccepted = form.querySelector<HTMLInputElement>('#dspaceLicense')!.checked;
    if (!licenseAccepted) {
      alert(i18next.t('license-error'));
      return;
    }

    const metadata = {
      metadata: [
        { key: 'dc.contributor.author', value: author },
        { key: 'dc.title', value: title },
        { key: 'dc.date.issued', value: date },
        { key: 'dc.type', value: type },
        { key: 'dc.description.abstract', value: abstract },
      ],
    };
    try {
      const res = await ApiC.send(Method.POST, 'dspace', {
        action: 'export',
        collection,
        metadata,
      });

      notify.success('export-success');
      $('#dspaceExportModal').modal('hide');
      console.log(res);
    } catch (e) {
      notify.error('submission-error');
      console.error(e);
    }
    //
    // try {
    //   // create the item's WORKSPACE in DSpace
    //   const workspace = await createWorkspaceItem(collection, metadata);
    //   console.log('workspace', workspace);
    //   return;
    //   const workspaceId = workspace.id;
    //   // get real DSpace item UUID to store be stored in elab)
    //   const itemUuid = await getItemUuidFromDspace(workspaceId);
    //   // patch eLabFTW metadata with the uuid
    //   await saveDspaceIdAsExtraField(itemUuid);
    //   // accept license (only reached if checkbox was checked)
    //   await acceptWorkspaceItemLicense(workspaceId);
    //   // patch DSpace's metadata section
    //   await updateWorkspaceItemMetadata(workspaceId, author, title, date, type, abstract);
    //   // mandatory file upload -> build ELN for current entry
    //   const elnFile = await buildCurrentEntryEln();
    //   await uploadWorkspaceItemFile(workspaceId, elnFile);
    //   // submit (deposit) to workflow. Catch here if the POST is not sent, otherwise the response time being >120sec we don't await it.
    //   // submitWorkspaceItemToWorkflow(workspaceId, token).catch(() => notify.error('submission-error'));
    //   await submitWorkspaceItemToWorkflow(workspaceId);
    //   notify.success('export-success');
    //   $('#dspaceExportModal').modal('hide');
    // } catch (e) {
    //   notify.error('submission-error');
    //   console.error(e);
    // }
  });
}

$('#dspaceExportModal').on('shown.bs.modal', async () => {
  const collectionSelect = document.getElementById('dspaceCollection') as HTMLSelectElement;
  const typeSelect = document.getElementById('dspaceType') as HTMLSelectElement;
  collectionSelect.innerHTML = '<option disabled selected>' + i18next.t('loading') + '...</option>';
  typeSelect.innerHTML = '<option disabled selected>' + i18next.t('loading') + '...</option>';

  try {
    // await ensureDspaceAuthFromBackend();
    const [collectionsJson, typesJson] = await Promise.all([
      listCollections(),
      listTypes(),
    ]);

    const collections = collectionsJson._embedded.collections;
    const types = typesJson._embedded.entries;
    // populate collections
    collectionSelect.innerHTML = '';
    collections.forEach((col: DspaceCollection) => {
      const opt = document.createElement('option');
      opt.value = col.uuid;
      opt.textContent = `${col.name} (${col.uuid})`;
      collectionSelect.appendChild(opt);
    });

    // populate types
    typeSelect.innerHTML = '';
    types.forEach((type: DspaceVocabularyEntry) => {
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
