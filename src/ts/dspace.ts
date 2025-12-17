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
import { DspaceCollection, DspaceVocabularyEntry, getCollections, getTypes, saveDspaceIdAsExtraField } from './dspaceUtils';
import i18next from './i18n';
import { FileType, Method } from './interfaces';
import { rememberLastSelected, selectLastSelected } from './localStorage';
import { notify } from './notify';
import { entity } from './getEntity';
import { TomSelect, collectForm } from './misc';
import { on } from './handlers';

on('export-to-dspace', async (_el, event: Event) => {
  event.preventDefault();
  const form = document.getElementById('dspaceExportForm') as HTMLFormElement;
  const params = collectForm(form);
  const licenseAccepted = form.querySelector<HTMLInputElement>('#dspaceLicense')!.checked;
  if (!licenseAccepted) {
    notify.error(i18next.t('license-error'));
    return;
  }
  const format = FileType.Eln;
  const metadata = [
    { key: 'dc.contributor.author', value: params['author'] || '' },
    { key: 'dc.title', value: params['title'] },
    { key: 'dc.date.issued', value: params['date'] || '' },
    { key: 'dc.type', value: params['type'] },
    { key: 'dc.description.abstract', value: params['abstract'] },
  ];

  try {
    const res = await ApiC.send(Method.PATCH, 'dspace', { collection: params['collection'], metadata, entity, format});
    const data = await res.json();
    const itemUuid = data.uuid;
    await saveDspaceIdAsExtraField(itemUuid);
    notify.success('export-success');
    $('#dspaceExportModal').modal('hide');
  } catch (e) {
    notify.error(e);
  }
});

// populate lists with tomSelect on modal show
on('open-dspace-modal', async () => {
  $('#dspaceExportModal').modal('toggle');
  const collectionSelect = document.getElementById('dspaceCollection') as HTMLSelectElement & { tomselect?: TomSelect };
  const typeSelect = document.getElementById('dspaceType') as HTMLSelectElement & { tomselect?: TomSelect };
  collectionSelect.innerHTML = `<option disabled selected>${i18next.t('loading')}...</option>`;
  typeSelect.innerHTML = `<option disabled selected>${i18next.t('loading')}...</option>`;
  try {
    const [collectionsJson, typesJson] = await Promise.all([
      getCollections(),
      getTypes(),
    ]);
    const collections = collectionsJson as DspaceCollection[];
    const types = typesJson._embedded.entries;

    // clear existing TomSelect if any
    if (collectionSelect?.tomselect) collectionSelect.tomselect.destroy();
    if (typeSelect?.tomselect) typeSelect.tomselect.destroy();
    // build select options
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
    ['dspaceCollection','dspaceType'].forEach(id => {
      new TomSelect(`#${id}`, {
        plugins: ['dropdown_input', 'no_active_items'],
        onChange: rememberLastSelected(id),
        onInitialize: selectLastSelected(id),
      });
    });
  } catch (e) {
    collectionSelect.innerHTML = `<option disabled selected>${i18next.t('error-fetch-request', { error: 'Collections' })}</option>`;
    typeSelect.innerHTML = `<option disabled selected>${i18next.t('error-fetch-request', { error: 'Types' })}</option>`;
    console.error(e);
  }
});
