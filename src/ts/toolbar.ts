/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import { ApiC } from './api';
import { entity } from './getEntity';
import { on } from './handlers';
import i18next from './i18n';
import { Action, Model } from './interfaces';
import { collectForm, relativeMoment, reloadElements, } from './misc';
import { notify } from './notify';

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

    try {
      // only login if not already logged in
      // const loggedIn = localStorage.getItem('dspaceLoggedIn');
      // if (!loggedIn) {
        await loginToDspace('toto@yopmail.com', 'totototototo');
      // }

      const token = await fetchXsrfToken();

      console.log('token from export-to-dspace', token);
      const createRes = await postToDspace({
        // url: `/dspace/api/submission/workspaceitems?embed=item,sections,collection&owningCollection=${collection}`,
        url: `/dspace/api/submission/workspaceitems?owningCollection=${collection}`,
        method: 'POST',
        token,
        contentType: 'application/json',
        body: JSON.stringify(metadata)
      });

      console.log('create res \n', createRes);
      if (!createRes.ok) {
        const errorText = await createRes.text();
        throw new Error(`Create failed: ${createRes.status} - ${errorText}`);
      }

      const item = await createRes.json();
      if (!item._links?.self?.href) {
        console.warn('Unexpected response format:', item);
        throw new Error('Invalid DSpace response: no self link');
      }

      const itemId = item.id;
      //
      // // 2. Accept license
      // await fetch(`/dspace/api/submission/workspaceitems/${itemId}`, {
      //   method: 'PATCH',
      //   headers: { 'Content-Type': 'application/json-patch+json' },
      //   credentials: 'include',
      //   body: JSON.stringify([
      //     { op: 'add', path: '/sections/license/granted', value: 'true' }
      //   ])
      // });
      //
      // // 3. Upload bitstream
      // const bitstreamUrl = item._links.self.href + '/bitstreams';
      // const fd = new FormData();
      // fd.append('file', file);
      //
      // const uploadRes = await fetch(bitstreamUrl, {
      //   method: 'POST',
      //   credentials: 'include',
      //   body: fd
      // });
      //
      // if (!uploadRes.ok) throw new Error('Bitstream upload failed');

      alert('Export to DSpace successful!');
    } catch (e) {
      console.error(e);
      alert(`Export failed: ${e.message}`);
    }
  });
}

let dspaceLoginInFlight: Promise<void> | null = null;

// export async function listCollections(): Promise<any> {
//   // const res = await fetch('/dspace/api/core/collections');
//   const res = await postToDspace({url: '/dspace/api/core/collections', method: 'GET'});
//   if (!res.ok) throw new Error(`DSpace error ${res.status}`);
//   const json = await res.json();
//   return json;
// }
export async function listCollections(): Promise<any> {
  const token = await fetchXsrfToken();
  const res = await postToDspace({url: '/dspace/api/core/collections', method: 'GET', token});
  if (!res.ok) throw new Error(`DSpace error ${res.status}`);
  return res.json();
}
// async function fetchXsrfToken(): Promise<string> {
//   const saved = localStorage.getItem('dspaceXsrfToken');
//   if (saved) return saved; // reuse cached token even if not logged in yet
//   const res = await fetch('dspace/api/security/csrf', {
//     method: 'GET',
//     credentials: 'include',
//   });
//   const token = res.headers.get('dspace-xsrf-token');
//   if (!token) throw new Error('No CSRF token found');
//   localStorage.setItem('dspaceXsrfToken', token);
//   console.log("token in fetch :", token);
//   return token;
// }

async function fetchXsrfToken(): Promise<string> {
  const res = await fetch('dspace/api/security/csrf', {
    method: 'GET',
    credentials: 'include',
  });
  const token = res.headers.get('dspace-xsrf-token');
  if (!token) throw new Error('No CSRF token found');
  localStorage.setItem('dspaceXsrfToken', token); // keep latest copy
  return token;
}

async function loginToDspace(user: string, password: string) {
  if (localStorage.getItem('dspaceLoggedIn') === 'true') return; // ✅ already logged in
  if (dspaceLoginInFlight) { await dspaceLoginInFlight; return; } // ✅ dedupe concurrent calls

  dspaceLoginInFlight = (async () => {
    const token = await fetchXsrfToken();
    console.log("token in login :", token);
    const body = new URLSearchParams({ user, password }).toString();
    const res = await postToDspace({url: 'dspace/api/authn/login', method: 'POST', token, body});
    if (!res.ok) {
      localStorage.removeItem('dspaceLoggedIn');
      localStorage.removeItem('dspaceXsrfToken');
      const error = await res.text();
      throw new Error(`Login failed: ${res.status} - ${error}`);
    }
    await res.text();
    console.log('Logged in successfully!');
    localStorage.setItem('dspaceLoggedIn', 'true');
  })();

  try { await dspaceLoginInFlight; } finally { dspaceLoginInFlight = null; }
}

async function isDspaceSessionActive(): Promise<boolean> {
  try {
    const res = await fetch('dspace/api/authn/status', { credentials: 'include' });
    if (!res.ok) return false;
    const data = await res.json();
    if (!data?.authenticated) {
      localStorage.removeItem('dspaceLoggedIn');
      localStorage.removeItem('dspaceXsrfToken');
      return false;
    }
    return true;
  } catch {
    return false;
  }
}

interface DspaceFetchOptions {
  url: string;
  method: string;
  token?: string | null;
  body?: BodyInit | null;
  contentType?: string;
}

const postToDspace = async ({ url, method, body = null, token = null, contentType = 'application/x-www-form-urlencoded' }: DspaceFetchOptions) => {
  const headers: Record<string,string> = { 'Content-Type': contentType };
  if (token) { headers['X-XSRF-TOKEN'] = token; headers['DSPACE-XSRF-COOKIE'] = token; } // ← only when present

  const res = await fetch(url, {
    method,
    headers,
    credentials: 'include',
    body,
  });

  const next = res.headers.get('dspace-xsrf-token');
  if (next) localStorage.setItem('dspaceXsrfToken', next); // keep latest token from server

  if (res.status === 401 || res.status === 403) {
    localStorage.removeItem('dspaceLoggedIn');
    localStorage.removeItem('dspaceXsrfToken');
  }
  return res;
};

// const postToDspace = async ({ url, method, body = null, token = null, contentType = 'application/x-www-form-urlencoded' }: DspaceFetchOptions) => {
//   const res = await fetch(url, {
//     method,
//     headers: {
//       'Content-Type': contentType,
//       'X-XSRF-TOKEN': token,
//       'DSPACE-XSRF-COOKIE': token,
//     },
//     credentials: 'include',
//     body,
//   });
//   // if (res.status === 401 || res.status === 403) {
//   //   localStorage.removeItem('dspaceLoggedIn');
//   //   localStorage.removeItem('dspaceXsrfToken');
//   // }
//   return res;
// };
// 1. get xsrf token
// 2. auth (login via email/password OR other methods)
// 3. post/patch etc (for 30 mins) & refresh if method is not Shibboleth
// s i shibboleth, est-ce que même idp que auth dans elab (cookie serait valide donc utilisable) ou il faut config new idp
// LOGIN: TODO: ask client their auth methods
// Called when modal is shown
$('#dspaceExportModal').on('shown.bs.modal', async () => {
  const select = document.getElementById('dspaceCollection') as HTMLSelectElement;
  select.innerHTML = '<option disabled selected>Loading...</option>';

  let active = await isDspaceSessionActive();
  if (!active) {
    await loginToDspace('toto@yopmail.com', 'totototototo'); // keeps { user, password }
    active = await isDspaceSessionActive();
  }

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
