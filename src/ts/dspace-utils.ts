/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Mouss <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

let dspaceLoginInFlight: Promise<void> | null = null;

// helper: get collections from DSpace
export async function listCollections(): Promise<any> {
  const token = await fetchXsrfToken();
  const res = await postToDspace({url: '/dspace/api/core/collections', method: 'GET', token});
  if (!res.ok) throw new Error(`DSpace error ${res.status}`);
  return res.json();
}

// helper: get types from DSpace
export async function listTypes(): Promise<any> {
  const token = await fetchXsrfToken();
  const res = await postToDspace({
    url: '/dspace/api/submission/vocabularies/common_types/entries', method: 'GET', token,
  });
  if (!res.ok) throw new Error(`DSpace error ${res.status}`);
  return res.json();
}

// helper: create an item in DSpace
export async function createWorkspaceItem(collection: string, metadata: any, token: string) {
  const res = await postToDspace({
    url: `/dspace/api/submission/workspaceitems?owningCollection=${collection}`,
    method: 'POST',
    token,
    contentType: 'application/json',
    body: JSON.stringify(metadata),
  });
  if (!res.ok) {
    const errorText = await res.text();
    throw new Error(`Create failed: ${res.status} - ${errorText}`);
  }
  return res.json();
}

// helper: accept license
export async function acceptWorkspaceItemLicense(itemId: number | string, token: string) {
  const res = await postToDspace({
    url: `/dspace/api/submission/workspaceitems/${itemId}`,
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
export async function updateWorkspaceItemMetadata(itemId: number | string, token: string, title: string, date: string, type: string, abstract: string) {
  const metaPatch = [
    { op: 'add', path: '/sections/traditionalpageone/dc.title', value: [{value: title, language: null}] },
    { op: 'add', path: '/sections/traditionalpageone/dc.date.issued', value: [{value: date, language: null}] },
    { op: 'add', path: '/sections/traditionalpageone/dc.type', value: [{value: type, language: null}] },
    { op: 'add', path: '/sections/traditionalpagetwo/dc.description.abstract', value: [{value: abstract, language: null}] },
  ];
  const res = await postToDspace({
    url: `/dspace/api/submission/workspaceitems/${itemId}`,
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
export async function uploadWorkspaceItemFile(itemId: number | string, file: File, token: string) {
  const fd = new FormData();
  fd.append('file', file);
  const res = await postToDspace({
    url: `/dspace/api/submission/workspaceitems/${itemId}`,
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
export async function submitWorkspaceItemToWorkflow(itemId: number | string, token: string) {
  const res = await postToDspace({
    url: '/dspace/api/workflow/workflowitems',
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

export async function fetchXsrfToken(): Promise<string> {
  const cached = localStorage.getItem('dspaceXsrfToken');
  if (cached && await isDspaceSessionActive()) return cached;
  const res = await fetch('dspace/api/security/csrf', { method: 'GET', credentials: 'include' });
  const token = res.headers.get('dspace-xsrf-token'); if (!token) throw new Error('No CSRF token found');
  localStorage.setItem('dspaceXsrfToken', token);
  return token;
}

export async function loginToDspace(user: string, password: string) {
  if (await isDspaceSessionActive()) {
    localStorage.setItem('dspaceLoggedIn', 'true');
    return;
  }
  if (dspaceLoginInFlight) { await dspaceLoginInFlight; return; }

  dspaceLoginInFlight = (async () => {
    const token = await fetchXsrfToken();
    const body = new URLSearchParams({ user, password }).toString();
    const res = await postToDspace({url: 'dspace/api/authn/login', method: 'POST', token, body});
    if (!res.ok) {
      localStorage.removeItem('dspaceLoggedIn');
      localStorage.removeItem('dspaceXsrfToken');
      localStorage.removeItem('dspaceAuth');
      const error = await res.text();
      throw new Error(`Login failed: ${res.status} - ${error}`);
    }
    const auth = res.headers.get('Authorization');
    if (auth) {
      localStorage.setItem('dspaceAuth', auth);
    }
    await res.text();
    localStorage.setItem('dspaceLoggedIn', 'true');
  })();

  try { await dspaceLoginInFlight; } finally { dspaceLoginInFlight = null; }
}

export async function isDspaceSessionActive(): Promise<boolean> {
  try {
    const auth = localStorage.getItem('dspaceAuth');
    const headers: Record<string,string> = {}; if (auth) headers['Authorization'] = auth;
    const res = await fetch('dspace/api/authn/status', { credentials: 'include', headers });
    if (!res.ok) return false;
    const data = await res.json();
    if (!data?.authenticated) {
      localStorage.removeItem('dspaceLoggedIn');
      localStorage.removeItem('dspaceXsrfToken');
      localStorage.removeItem('dspaceAuth');
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

export const postToDspace = async ({ url, method, body = null, token = null, contentType = 'application/x-www-form-urlencoded' }: DspaceFetchOptions) => {
  const headers: Record<string,string> = {};
  if (contentType) headers['Content-Type'] = contentType;
  const auth = localStorage.getItem('dspaceAuth');
  if (auth) headers['Authorization'] = auth;
  if (token) headers['X-XSRF-TOKEN'] = token;

  const res = await fetch(url, { method, headers, credentials: 'include', body });

  const next = res.headers.get('dspace-xsrf-token'); if (next) localStorage.setItem('dspaceXsrfToken', next);
  if (res.status === 401 || res.status === 403) {
    localStorage.removeItem('dspaceLoggedIn');
    localStorage.removeItem('dspaceXsrfToken');
    localStorage.removeItem('dspaceAuth');
  }
  return res;
};
