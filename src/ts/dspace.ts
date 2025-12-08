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
import { ApiC } from './api';
import { DspaceCollection, DspaceVocabularyEntry, listCollections, listTypes, saveDspaceIdAsExtraField } from './dspaceUtils';
import { on } from './handlers';
import i18next from './i18n';
import { FileType, Method } from './interfaces';
import { notify } from './notify';
import { entity } from './getEntity';

if (document.getElementById('dspaceExportModal')) {
  on('export-to-dspace', async () => {
    const form = document.getElementById('dspaceExportForm') as HTMLFormElement;
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
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
      const format = FileType.Eln;
      const metadata = [
        { key: 'dc.contributor.author', value: author },
        { key: 'dc.title', value: title },
        { key: 'dc.date.issued', value: date },
        { key: 'dc.type', value: type },
        { key: 'dc.description.abstract', value: abstract },
      ];
      console.log('metdata', metadata);
      try {
        const res = await ApiC.send(Method.PATCH, 'dspace', { collection,  metadata,  entity, format });
        const data = await res.json();
        const itemUuid = data.uuid;
        await saveDspaceIdAsExtraField(itemUuid);
        notify.success('export-success');
        $('#dspaceExportModal').modal('hide');
      } catch (e) {
        notify.error(e);
      }
    })
  });
}

$('#dspaceExportModal').on('shown.bs.modal', async () => {
  const collectionSelect = document.getElementById('dspaceCollection') as HTMLSelectElement;
  const typeSelect = document.getElementById('dspaceType') as HTMLSelectElement;
  collectionSelect.innerHTML = '<option disabled selected>' + i18next.t('loading') + '...</option>';
  typeSelect.innerHTML = '<option disabled selected>' + i18next.t('loading') + '...</option>';

  try {
    const [collectionsJson, typesJson] = await Promise.all([
      listCollections(),
      listTypes(),
    ]);
    // populate collections and types select for modal
    const collections = collectionsJson as DspaceCollection[];
    const types = typesJson._embedded.entries;

    collectionSelect.innerHTML = '';
    collections.forEach((col: DspaceCollection) => {
      const opt = document.createElement('option');
      opt.value = col.uuid;
      opt.textContent = `${col.name} (${col.uuid})`;
      collectionSelect.appendChild(opt);
    });
    typeSelect.innerHTML = '';
    types.forEach((type: DspaceVocabularyEntry) => {
      const opt = document.createElement('option');
      opt.value = type.value;
      opt.textContent = type.display;
      typeSelect.appendChild(opt);
    });
  } catch (e) {
    collectionSelect.innerHTML = `<option disabled selected>${i18next.t('error-fetch-request', { error: 'Collections' })}</option>`;
    typeSelect.innerHTML = `<option disabled selected>${i18next.t('error-fetch-request', { error: 'Types' })}</option>`;
    console.error(e);
  }
});
