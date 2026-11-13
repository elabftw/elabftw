/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Method } from './interfaces';
import type { Selected } from './interfaces';
import { getNewIdFromPostRequest } from './misc';
import { notify } from './notify';
import { downloadBlob, downloadFilename } from './download';

type ApiParams = Record<string, unknown> | FormData | object | Selected;

export class Api {
  // allow forcing the browser to make the request even if page is closed − useful for clearing exclusive edit on window unload
  // it is false by default for two reasons:
  // 1. It is not needed
  // 2. This bug: https://bugzilla.mozilla.org/show_bug.cgi?id=1926042
  keepalive = false;

  get(query: string, params = {}): Promise<Response> {
    return this.send(Method.GET, query, params);
  }

  // TODO remove any default type and type all calls to getJson
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  async getJson<T = any>(query: string, params = {}): Promise<T> {
    return this.get(query, params).then(resp => resp.json());
  }

  // fetch a binary file from a GET request, and make client download it
  async getBlob(query: string, filename: string): Promise<void> {
    try {
      // This method owns the notification so request failures are reported only once.
      const response = await this.get(query, { notifOnError: 0 });
      const resolvedName = downloadFilename(response.headers.get('Content-Disposition'), filename);
      downloadBlob(await response.blob(), resolvedName);
    } catch (error) {
      notify.error('error-fetch-request', { error: String(error) });
      throw error;
    }
  }

  patch(query: string, params: ApiParams = {}): Promise<Response> {
    return this.send(Method.PATCH, query, params);
  }

  post(query: string, params: ApiParams = {}): Promise<Response> {
    return this.send(Method.POST, query, params);
  }

  async post2location(query: string, params = {}): Promise<number> {
    return this.send(Method.POST, query, params).then(res => getNewIdFromPostRequest(res));
  }


  delete(query: string, params = {}): Promise<Response> {
    return this.send(Method.DELETE, query, params);
  }

  // private method: use patch/post/delete instead
  private async send(method: Method, query: string, params: ApiParams = {}): Promise<Response> {
    // Notification flags are transport options; do not delete them from caller-owned objects.
    const payload = params instanceof FormData ? new FormData() : { ...params };
    if (params instanceof FormData && payload instanceof FormData) {
      params.forEach((value, key) => payload.append(key, value));
    }
    const isFormData = payload instanceof FormData;


    // allow toggle notifs off by sending notifOn(Saved|Error)=0 as param
    let notifOnSaved = true;
    let notifOnError = true;
    if (isFormData) {
      // FormData values are strings, and the flags must not reach the server as form fields
      notifOnSaved = payload.get('notifOnSaved') !== '0';
      notifOnError = payload.get('notifOnError') !== '0';
      payload.delete('notifOnSaved');
      payload.delete('notifOnError');
    } else {
      if (payload['notifOnSaved'] === 0) {
        notifOnSaved = false;
      }
      delete payload['notifOnSaved'];

      if (payload['notifOnError'] === 0) {
        notifOnError = false;
      }
      delete payload['notifOnError'];
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const headers: HeadersInit = {
      'X-CSRF-Token': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
    };

    if (!isFormData) {
      headers['Content-Type'] = 'application/json';
    }

    const options: RequestInit = {
      method: method,
      headers: headers,
      keepalive: this.keepalive,
    };

    if ([Method.POST, Method.PATCH].includes(method)) {
      options.body = isFormData ? payload : JSON.stringify(payload);
    }

    let urlParams = '';
    if (method === Method.GET && !isFormData && Object.keys(payload).length > 0) {
      urlParams = `${query.includes('?') ? '&' : '?'}${new URLSearchParams(payload as Record<string, string>).toString()}`;
    }

    return fetch(`api/v2/${query}${urlParams}`, options).then(async response => {
      if (response.status !== this.getOkStatusFromMethod(method)) {
        let message = `HTTP ${response.status}${response.statusText ? `: ${response.statusText}` : ''}`;
        try {
          const json: unknown = await response.json();
          if (json && typeof json === 'object') {
            const details = json as { message?: unknown; description?: unknown };
            const description = typeof details.message === 'string' && details.message.trim()
              ? details.message : details.description;
            if (typeof description === 'string' && description.trim()) message = description;
          }
        } catch {
          // A proxy can return HTML or an empty body. Preserve the HTTP error and status.
        }
        const error = new Error(message) as Error & { status?: number };
        error.status = response.status;
        throw error;
      }
      return response;
    }).then(response => {
      if (method !== Method.GET && notifOnSaved) {
        notify.success();
      }
      return response;
    }).catch(error => {
      if (notifOnError) {
        notify.error(error.message);
      }

      throw error;
    });
  }

  private getOkStatusFromMethod(method: Method): number {
    switch (method) {
    case Method.GET:
    case Method.PATCH:
      return 200;
    case Method.POST:
      return 201;
    case Method.DELETE:
      return 204;
    default:
      return 200;
    }
  }

}
