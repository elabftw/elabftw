/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { ApiC } from "./api";
import { entity } from './getEntity';
import { Method } from "./interfaces";
import JsonEditorHelper from './JsonEditorHelper.class';
import { Metadata } from './Metadata.class';
import { ExtraFieldInputType, ValidMetadata } from './metadataInterfaces';

const apiUrl = '';

/*
 * ALL Dspace GET METHODS
 */
function buildDspaceUrl(action: string): URL {
  const url = new URL('/api/v2/dspace', window.location.origin);
  url.searchParams.set('dspace_action', action);
  return url;
}

// GET Dspace token
export async function ensureDspaceAuthFromBackend(): Promise<string> {
  if (localStorage.getItem('dspaceAuth') && localStorage.getItem('dspaceXsrfToken')) {
    return localStorage.getItem('dspaceAuth')!;
  }
  const res = await fetch(buildDspaceUrl('auth'));
  if (!res.ok) {
    const text = await res.text();
    throw new Error(`DSpace backend login failed: ${res.status} - ${text}`);
  }
  const { auth, xsrf } = await res.json();
  localStorage.setItem('dspaceAuth', auth);
  localStorage.setItem('dspaceXsrfToken', xsrf);
  return auth;
}

// GET list of collections
export async function listCollections(): Promise<DspaceCollectionList> {
  const res = await fetch(buildDspaceUrl('collections'));
  if (!res.ok) throw new Error(`DSpace collections error: ${res.status}`);
  return await res.json() as DspaceCollectionList;
}

// GET list of types
export async function listTypes(): Promise<DspaceVocabularyEntryList> {
  const res = await fetch(buildDspaceUrl('types'));
  if (!res.ok) throw new Error(`DSpace types error: ${res.status}`);
  return await res.json() as DspaceVocabularyEntryList;
}

// GET item uuid
export async function getItemUuidFromDspace(workspaceId: number | string): Promise<string> {
  const url = buildDspaceUrl('itemUuid');
  url.searchParams.set('workspaceId', String(workspaceId));

  const res = await fetch(url.toString());
  if (!res.ok) {
    const text = await res.text();
    throw new Error(`DSpace UUID fetch failed: ${res.status} - ${text}`);
  }
  const json = await res.json() as DspaceItem;
  return json.uuid;
}

/**
 * ALL POST METHODS
 */
export async function createWorkspaceItem(
  collection: string,
  metadata: DspaceWorkspaceItemMetadata,
): Promise<DspaceWorkspaceItem> {
  const auth = localStorage.getItem('dspaceAuth');
  const xsrf = localStorage.getItem('dspaceXsrfToken');

  const res = await ApiC.send(Method.POST, 'dspace', {
    action: 'create',
    collection,
    metadata,
    // keep headers inside body for now
    headers: {
      Authorization: auth,
      'X-XSRF-TOKEN': xsrf,
    },
  });

  return res.json() as Promise<DspaceWorkspaceItem>;
}

// export async function createWorkspaceItem(
//   collection: string,
//   metadata: DspaceWorkspaceItemMetadata,
// ): Promise<DspaceWorkspaceItem> {
//   const token = await fetchXsrfToken();
//   const url = `${apiUrl}submission/workspaceitems?owningCollection=${collection}`;
//   const res = await postToDspace({
//     url,
//     method: 'POST',
//     token,
//     contentType: 'application/json',
//     body: JSON.stringify(metadata),
//   });
//   console.log('res', res);
//   if (!res.ok) {
//     const errorText = await res.text();
//     throw new Error(`Create failed: ${res.status} - ${errorText}`);
//   }
//   return await res.json() as Promise<DspaceWorkspaceItem>;
// }

// helper: accept license
export async function acceptWorkspaceItemLicense(itemId: number | string) {
  const token = await fetchXsrfToken();
  const res = await postToDspace({
    url: `${apiUrl}submission/workspaceitems/${itemId}`,
    method: 'PATCH',
    token,
    contentType: 'application/json-patch+json',
    body: JSON.stringify([
      {op: 'add', path: '/sections/license/granted', value: 'true'},
    ]),
  });
  if (!res.ok) {
    const errorText = await res.text();
    throw new Error(`License patch failed: ${res.status} - ${errorText}`);
  }
  return res;
}

// helper: patch metadata in traditionalpage (1st page of the item)
export async function updateWorkspaceItemMetadata(itemId: number | string, author: string, title: string, date: string, type: string, abstract: string) {
  const token = await fetchXsrfToken();
  const metaPatch = [
    { op: 'add', path: '/sections/traditionalpageone/dc.contributor.author', value: [{value: author, language: null}] },
    { op: 'add', path: '/sections/traditionalpageone/dc.title', value: [{value: title, language: null}] },
    { op: 'add', path: '/sections/traditionalpageone/dc.date.issued', value: [{value: date, language: null}] },
    { op: 'add', path: '/sections/traditionalpageone/dc.type', value: [{value: type, language: null}] },
    { op: 'add', path: '/sections/traditionalpagetwo/dc.description.abstract', value: [{value: abstract, language: null}] },
  ];
  const res = await postToDspace({
    url: `${apiUrl}submission/workspaceitems/${itemId}`,
    method: 'PATCH',
    token,
    contentType: 'application/json-patch+json',
    body: JSON.stringify(metaPatch),
  });
  if (!res.ok) {
    const errorText = await res.text();
    throw new Error(`Metadata patch failed: ${res.status} - ${errorText}`);
  }
  return res;
}

// helper: upload a file to the created item in DSpace
export async function uploadWorkspaceItemFile(itemId: number | string, file: File) {
  const token = await fetchXsrfToken();
  const fd = new FormData();
  fd.append('file', file);
  const res = await postToDspace({
    url: `${apiUrl}submission/workspaceitems/${itemId}`,
    method: 'POST',
    token,
    contentType: null,
    body: fd,
  });
  if (!res.ok) {
    const errorText = await res.text();
    throw new Error(`File upload failed: ${res.status} - ${errorText}`);
  }
  return res;
}

// helper: publish the workspaceitem to workflow (deposit)
export async function submitWorkspaceItemToWorkflow(itemId: number | string) {
  const token = await fetchXsrfToken();
  const res = await postToDspace({
    url: `${apiUrl}workflow/workflowitems`,
    method: 'POST',
    token,
    contentType: 'text/uri-list',
    body: `/api/submission/workspaceitems/${itemId}`,
  });
  if (!res.ok) {
    const errorText = await res.text();
    throw new Error(`Submit to workflow failed: ${res.status} - ${errorText}`);
  }
  return res;
}

// export async function getItemUuidFromDspace(workspaceId: number | string): Promise<string> {
//   const token = await fetchXsrfToken();
//   const res = await postToDspace({
//     url: `${apiUrl}submission/workspaceitems/${workspaceId}/item`,
//     method: 'GET',
//     token,
//   });
//   if (!res.ok) {
//     const errorText = await res.text();
//     throw new Error(`Get item from workspaceitem failed: ${res.status} - ${errorText}`);
//   }
//   const item = await res.json() as DspaceItem;
//   return item.uuid;
// }

export async function fetchXsrfToken(): Promise<string> {
  const cached = localStorage.getItem('dspaceXsrfToken');
  if (cached) return cached;
  const res = await fetch(`${apiUrl}security/csrf`, { method: 'GET',  credentials: 'include' });
  const token = res.headers.get('dspace-xsrf-token');
  if (!token) throw new Error('No CSRF token found');
  localStorage.setItem('dspaceXsrfToken', token);
  return token;
}

//
// export const postToDspace = async ({ url, method, body = null, token = null, contentType = 'application/x-www-form-urlencoded' }: DspaceFetchOptions) => {
//   const headers: Record<string,string> = {};
//   if (contentType) headers['Content-Type'] = contentType;
//   // if (auth) headers['Authorization'] = auth;
//   if (token) headers['X-XSRF-TOKEN'] = token;
//
//   const auth = localStorage.getItem('dspaceAuth');
//   if (auth && auth.startsWith('Bearer ')) {
//     headers['Authorization'] = auth;
//   }
//
//   const res = await fetch(url, { method, headers, credentials: 'include', body });
//
//   const next = res.headers.get('dspace-xsrf-token'); if (next) localStorage.setItem('dspaceXsrfToken', next);
//   if (res.status === 401 || res.status === 403) {
//     localStorage.removeItem('dspaceLoggedIn');
//     localStorage.removeItem('dspaceXsrfToken');
//     localStorage.removeItem('dspaceAuth');
//   }
//   return res;
// };

export const postToDspace = async ({url, method, body = null, token = null, contentType = 'application/x-www-form-urlencoded'}: DspaceFetchOptions) => {
  const headers: Record<string, string> = {};
  if (contentType) {
    headers['Content-Type'] = contentType;
  }
  // add Authorization: Bearer <token> from localStorage
  const auth = localStorage.getItem('dspaceAuth');
  if (auth) {
    headers['Authorization'] = auth.startsWith('Bearer ') ? auth : `Bearer ${auth}`;
  }
  // add XSRF token
  if (token) {
    headers['X-XSRF-TOKEN'] = token;
  }

  const res = await fetch(url, { method, headers, credentials: 'include', body });
  // update XSRF token if it rotates
  const next = res.headers.get('dspace-xsrf-token');
  if (next) {
    localStorage.setItem('dspaceXsrfToken', next);
  }
  if (res.status === 401 || res.status === 403) {
    localStorage.removeItem('dspaceLoggedIn');
    localStorage.removeItem('dspaceXsrfToken');
    localStorage.removeItem('dspaceAuth');
  }
  return res;
};

export async function saveDspaceIdAsExtraField(itemUuid: string): Promise<void> {
  const MetadataC = new Metadata(entity, new JsonEditorHelper(entity));
  const raw = await MetadataC.read();
  const metadata = (raw || {}) as ValidMetadata;
  if (!metadata.extra_fields) {
    metadata.extra_fields = {};
  }

  metadata.extra_fields['DSpace id'] = {
    type: ExtraFieldInputType.Text,
    value: itemUuid,
    description: 'Uuid handle from DSpace',
    readonly: true,
  };

  const mode = new URLSearchParams(window.location.search).get('mode');
  await MetadataC.save(metadata).then(() => MetadataC.display(mode === 'edit' ? 'edit' : 'view'));
}

export async function buildCurrentEntryEln(): Promise<File> {
  const res = await fetch(`api/v2/${entity.type}/${entity.id}?format=eln`, {
    method: 'GET',
    credentials: 'include',
  });
  if (!res.ok) {
    const text = await res.text();
    throw new Error(`ELN export failed: ${res.status} - ${text}`);
  }

  const blob = await res.blob();
  const filename = `elabftw-${entity.type}-${entity.id}.eln`;

  return new File([blob], filename, {
    type: 'application/vnd.eln+zip',
  });
}

interface DspaceFetchOptions {
  url: string;
  method: string;
  token?: string | null;
  body?: BodyInit | null;
  contentType?: string | null;
}

export interface DspaceCollection {
  uuid: string;
  name: string;
  [key: string]: unknown;
}

export interface DspaceCollectionList {
  _embedded: {
    collections: DspaceCollection[];
  };
  [key: string]: unknown;
}

export interface DspaceVocabularyEntry {
  value: string;
  display: string;
  [key: string]: unknown;
}

export interface DspaceVocabularyEntryList {
  _embedded: {
    entries: DspaceVocabularyEntry[];
  };
  [key: string]: unknown;
}

interface DspaceMetadataEntry {
  key: string;
  value: string;
}

export interface DspaceWorkspaceItemMetadata {
  metadata: DspaceMetadataEntry[];
}

interface DspaceWorkspaceItemLinks {
  self?: { href: string };
  [key: string]: unknown;
}

export interface DspaceWorkspaceItem {
  id: number;
  _links?: DspaceWorkspaceItemLinks;
  [key: string]: unknown;
}

interface DspaceItem {
  uuid: string;
  handle?: string;
  [key: string]: unknown;
}

// // FOR LATER
// // PATCH ACTIONS
// async function patchDspace(action: string, params: Record<string, any>): Promise<Response> {
//   const res = await fetch('/api/v2/dspace', {
//     method: 'PATCH',
//     headers: { 'Content-Type': 'application/json' },
//     body: JSON.stringify({ action: 'update', dspace_action: action, ...params }),
//   });
//   if (!res.ok) {
//     const text = await res.text();
//     throw new Error(`DSpace ${action} failed: ${res.status} - ${text}`);
//   }
//   return res;
// }
//
// export function acceptWorkspaceItemLicense(itemId: number | string): Promise<Response> {
//   return patchDspace('acceptLicense', { workspaceId: itemId });
// }
//
// export function updateWorkspaceItemMetadata(
//   itemId: number | string,
//   author: string,
//   title: string,
//   date: string,
//   type: string,
//   abstract: string
// ): Promise<Response> {
//   return patchDspace('updateMetadata', { itemId, author, title, date, type, abstract });
// }
//
// export function uploadWorkspaceItemFile(itemId: number | string, file: File): Promise<Response> {
//   const formData = new FormData();
//   formData.append('file', file);
//   return fetch('/api/v2/dspace', {
//     method: 'PATCH',
//     body: formData,
//   });
// }
//
// export function submitWorkspaceItemToWorkflow(itemId: number | string): Promise<Response> {
//   return patchDspace('submitToWorkflow', { workspaceId: itemId });
// }
