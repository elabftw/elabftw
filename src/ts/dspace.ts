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
import { DspaceCollection, DspaceVocabularyEntryList, DspaceVocabularyEntry, saveDspaceIdAsExtraField } from './dspaceUtils';
import i18next from './i18n';
import { Action, FileType, Method } from './interfaces';
import { rememberLastSelected, selectLastSelected } from './localStorage';
import { notify } from './notify';
import { entity } from './getEntity';
import { TomSelect, collectForm, mkSpin, mkSpinStop } from './misc';
import { on } from './handlers';
import $ from 'jquery';

on('export-to-dspace', async (el, event: Event) => {
  const btn = el as HTMLButtonElement;
  event.preventDefault();
  const form = document.getElementById('dspaceExportForm') as HTMLFormElement;
  const params = collectForm(form);
  const format = FileType.Eln;
  const metadata = [
    { key: 'dc.contributor.author', value: params['author'] || '' },
    { key: 'dc.title', value: params['title'] },
    { key: 'dc.date.issued', value: params['date'] || '' },
    { key: 'dc.type', value: params['type'] },
    { key: 'dc.description.abstract', value: params['abstract'] },
  ];
  const oldHTML = mkSpin(btn);
  const prevNotifOnSaved = ApiC.notifOnSaved;
  const prevNotifOnError = ApiC.notifOnError;
  try {
    ApiC.notifOnSaved = false;
    ApiC.notifOnError = false;
    const res = await ApiC.send(Method.PATCH, 'dspace', { collection: params['collection'], metadata, entity, format});
    const data = await res.json();
    const itemPublicUrl = data.publicUrl;
    await saveDspaceIdAsExtraField(itemPublicUrl);
    notify.success('export-success');
    $('#dspaceExportModal').modal('hide');
  } catch (e) {
    notify.error(e);
  } finally {
    ApiC.notifOnSaved = prevNotifOnSaved;
    ApiC.notifOnError = prevNotifOnError;
    mkSpinStop(btn, oldHTML);
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
    const [collections, typesJson] = await Promise.all([
      ApiC.getJson<DspaceCollection[]>('dspace', {action: Action.GetCollections}),
      ApiC.getJson<DspaceVocabularyEntryList>('dspace', {action: Action.GetTypes}),
    ]);
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
