/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import {
  collectForm,
  relativeMoment,
  reloadElements,
} from './misc';
import i18next from './i18n';
import $ from 'jquery';
import { Action, Model } from './interfaces';
import { notify } from './notify';
import { ApiC } from './api';
import { entity } from './getEntity';
import { on } from './handlers';

if (document.getElementById('topToolbar')) {
  on(Action.Duplicate, () => {
    const copyFiles = (document.getElementById('duplicateKeepFilesSelect') as HTMLInputElement);
    const linkToOriginalExperiment = (document.getElementById('duplicateLinkToOriginal') as HTMLInputElement);
    // Ensure the link to original exists because this feature is not available for Template entities
    ApiC.post2location(`${entity.type}/${entity.id}`, {
      action: Action.Duplicate,
      copyFiles: Boolean(copyFiles.checked),
      linkToOriginal: Boolean(linkToOriginalExperiment?.checked ?? false)},
    ).then(id => {
      window.location.href = `?mode=edit&id=${id}`;
    });
  });

  on(Action.Timestamp, () => {
    ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Timestamp}).then(() => {
      reloadElements(['requestActionsDiv', 'isTimestampedByInfoDiv']);
    }).catch(error => {
      notify.error(error);
    });
  });

  on(Action.Bloxberg, () => {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    const loading = document.createElement('p');
    const ring = document.createElement('div');
    ring.classList.add('lds-dual-ring');
    // see https://loading.io/css/
    const emptyDiv = document.createElement('div');
    ring.appendChild(emptyDiv);
    ring.appendChild(emptyDiv);
    ring.appendChild(emptyDiv);
    ring.appendChild(emptyDiv);
    overlay.classList.add('full-screen-overlay');
    loading.appendChild(ring);
    overlay.appendChild(loading);
    document.getElementById('container').append(overlay);
    ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Bloxberg})
      // reload uploaded files on success
      .then(() => reloadElements(['uploadsDiv']))
      // remove overlay in all cases
      .finally(() => document.getElementById('container').removeChild(document.getElementById('loadingOverlay')));
  });

  on(Action.Sign, (_, event: Event) => {
    event.preventDefault();
    const form = document.getElementById('sigPassphraseForm') as HTMLFormElement;
    const params = collectForm(form);
    params['action'] = Action.Sign;
    ApiC.patch(`${entity.type}/${entity.id}`, params).then(() => {
      reloadElements(['commentsDiv', 'requestActionsDiv']);
      form.reset();
      $('#addSignatureModal').modal('hide');
    });
  });

  on(Action.RequestAction, () => {
    const actionSelect = (document.getElementById('requestActionActionSelect') as HTMLSelectElement);
    const userSelect = (document.getElementById('requestActionUserSelect') as HTMLSelectElement);
    ApiC.post(`${entity.type}/${entity.id}/request_actions`, {
      action: Action.Create,
      target_action: actionSelect.value,
      target_userid: parseInt(userSelect.value.split(' ')[0], 10),
    }).then(() => reloadElements(['requestActionsDiv']))
      .then(() => relativeMoment())
      // the request gets rejected if repeated
      .catch(error => console.error(error.message));
  });

  on('do-requestable-action', (el: HTMLElement) => {
    switch (el.dataset.target) {
    case Action.Archive:
      // reload the page to avoid further actions on the entity (in edit mode), also refreshing gets to "You cannot edit it!" page. (See #5552)
      ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Archive})
        .then(() => window.location.href = `?mode=view&id=${entity.id}`);
      break;
    case Action.Unarchive:
      ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Unarchive})
        .then(() => window.location.href = `?mode=view&id=${entity.id}`);
      break;
    case Action.Lock:
      // reload the page to change the icon and make the edit button disappear (#1897)
      ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Lock})
        .then(() => window.location.href = `?mode=view&id=${entity.id}`);
      break;
    case Action.Review:
      ApiC.patch(`${entity.type}/${entity.id}`, {action: Action.Review})
        .then(() => window.location.href = `?mode=view&id=${entity.id}`);
      break;
    case Action.Timestamp:
      $('#timestampModal').modal('toggle');
      break;
    case Action.Sign:
      $('#addSignatureModal').modal('toggle');
      break;
    }
  });

  on(Action.CancelRequestableAction, (el: HTMLElement) => {
    if (confirm(i18next.t('generic-delete-warning'))) {
      ApiC.delete(`${entity.type}/${entity.id}/request_actions/${el.dataset.id}`)
        .then(() => el.parentElement.parentElement.parentElement.parentElement.remove());
    }
  });

  on('export-to', (el: HTMLElement) => {
    const format = el.dataset.format;
    const changelog = (document.getElementById(`${format}_exportWithChangelog`) as HTMLInputElement).checked ? 1 : 0;
    const classification = (document.getElementById(`${format}_exportClassification`) as HTMLSelectElement).value;
    let json = 0;
    if (format === 'zip') {
      json = (document.getElementById(`${format}_exportJson`) as HTMLInputElement).checked ? 1 : 0;
    }
    const finalFormat = (document.getElementById(`${format}_exportPdfa`) as HTMLInputElement).checked ? format + 'a' : format;
    window.open(`/api/v2/${el.dataset.type}/${el.dataset.id}?format=${finalFormat}&changelog=${changelog}&json=${json}&classification=${classification}`, '_blank');
  });

  on('export-to-qrpng', (el: HTMLElement) => {
    const size = (document.getElementById('qrpng_exportSize') as HTMLInputElement).value;
    const title = (document.getElementById('qrpng_exportTitle') as HTMLInputElement).checked ? 1: 0;
    const titleLines = (document.getElementById('qrpng_exportTitleLines') as HTMLInputElement).value;
    const titleChars = (document.getElementById('qrpng_exportTitleChars') as HTMLInputElement).value;
    window.open(`/api/v2/${el.dataset.type}/${el.dataset.id}?format=qrpng&size=${size}&withTitle=${title}&titleLines=${titleLines}&titleChars=${titleChars}`, '_blank');
  });


  on('get-collections', (el: HTMLElement) => {

    listCollections().then(res => console.log(res));
    // console.log(el);
    // fetch('/dspace/').then(res => res.json()).then(data => console.log(data));
    // fetch('/dspace/server/api/core/collections')
    //   .then(res => res.json())
    //   .then(data => console.log(data));
  });

  on(Action.Destroy, () => {
    if (confirm(i18next.t('generic-delete-warning'))) {
      const path = window.location.pathname;
      ApiC.delete(`${entity.type}/${entity.id}`).then(
        () => window.location.replace(path.split('/').pop()));
    }
  });

  on(Action.CreateProcurementRequest, () => {
    const input = (document.getElementById('procurementRequestQtyInput') as HTMLInputElement);
    const qty = parseInt(input.value, 10);
    // sanity check
    if (qty < 1) {
      notify.error('invalid-info');
      return;
    }
    ApiC.post(`${Model.Team}/current/procurement_requests`, {entity_id: entity.id, qty_ordered: qty});
  });

  on('export-to-dspace', async (el: HTMLElement) => {
    const form = document.getElementById('dspaceExportForm') as HTMLFormElement;
    const collection = form.collection.value;
    const author = form.author.value;
    const title = form.title;
    const date = form.date.value;
    const type = form.type.value;
    const abstract = form.abstract.value;
    const file = form.file.files[0];
    const licenseAccepted = form.querySelector<HTMLInputElement>('#dspaceLicense')!.checked;

    if (!licenseAccepted) {
      alert('You must accept the license.');
      return;
    }

    const metadata = {
      metadata: [
        { key: 'dc.creator', value: author },
        { key: 'dc.title', value: title },
        { key: 'dc.date.issued', value: date },
        { key: 'dc.type', value: type },
        { key: 'dc.description.abstract', value: abstract }
      ]
    };

    // try {
    //   // 1. Create a WorkspaceItem in the collection (assuming your version supports it)
    //   const createRes = await fetch(`/dspace/api/submission/workspaceitems?embed=item,sections,collection&owningCollection=${collection}`, {
    //     method: 'POST',
    //     headers: {
    //       'Content-Type': 'application/json',
    //     },
    //     body: JSON.stringify(metadata)
    //   });
    //   console.log(createRes);
    //   if (!createRes.ok) {
    //     const errorText = await createRes.text();
    //     throw new Error(`Create failed: ${createRes.status} - ${errorText}`);
    //   }
    //
    //   const item = await createRes.json();
    //   if (!item._links?.self?.href) {
    //     console.warn('Unexpected response format:', item);
    //     throw new Error('Invalid DSpace response: no self link');
    //   }
    //
    //   const itemId = item.id;
    //
    //   // 2. Accept license
    //   await fetch(`/dspace/api/submission/workspaceitems/${itemId}`, {
    //     method: 'PATCH',
    //     headers: {
    //       'Content-Type': 'application/json-patch+json',
    //       // 'X-XSRF-TOKEN': csrfToken,
    //       // 'Authorization': `Bearer ${token}`
    //     },
    //     credentials: 'include',
    //     body: JSON.stringify([
    //       { op: 'add', path: '/sections/license/granted', value: 'true' }
    //     ])
    //   });
    //
    //   // 3. Upload bitstream
    //   const bitstreamUrl = item._links.self.href + '/bitstreams';
    //   const fd = new FormData();
    //   fd.append('file', file);
    //
    //   const uploadRes = await fetch(bitstreamUrl, {
    //     method: 'POST',
    //     credentials: 'include',
    //     headers: {
    //       // 'X-XSRF-TOKEN': csrfToken,
    //       // 'Authorization': `Bearer ${token}`
    //     },
    //     body: fd
    //   });
    //
    //   if (!uploadRes.ok) throw new Error('Bitstream upload failed');
    //
    //   alert('Export to DSpace successful!');
    // } catch (e) {
    //   console.error(e);
    //   alert(`Export failed: ${e.message}`);
    // }
  })
}

export async function listCollections(): Promise<any> {
  const res = await fetch('/dspace/api/core/collections');
  if (!res.ok) throw new Error(`DSpace error ${res.status}`);
  const json = await res.json();
  console.log(res, json);
  return json;
}

// Called when modal is shown
$('#dspaceExportModal').on('shown.bs.modal', async () => {
  console.log("hi modal is up");
  const select = document.getElementById('dspaceCollection') as HTMLSelectElement;
  select.innerHTML = '<option disabled selected>Loading...</option>';

  try {
    const json = await listCollections();
    const collections = json._embedded.collections;
    select.innerHTML = '';
    collections.forEach((col: any) => {
      const opt = document.createElement('option');
      opt.value = col.uuid;
      opt.textContent = `${col.name} (${col.uuid})`;
      select.appendChild(opt);
    });
  } catch (e) {
    select.innerHTML = '<option disabled>Error loading collections</option>';
    console.error(e);
  }
});
