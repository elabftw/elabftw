/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Method, Selected } from './interfaces';
import { getNewIdFromPostRequest } from './misc';
import { notify } from './notify';

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
    this.get(query).then(async resp => {
      const disposition = resp.headers.get('Content-Disposition');
      if (disposition && disposition.includes('filename=')) {
        const filenameMatch = disposition.match(/filename="(.+)"/);
        if (filenameMatch.length > 1) {
          filename = filenameMatch[1];
        }
      }
      return resp.blob().then(blob => ({ blob, filename }));
    }).then(({ blob, filename }) => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
    }).catch(error => {
      notify.error('error-fetch-request', { error });
    });
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
    const isFormData = params instanceof FormData;


    // allow toggle notifs off by sending notifOn(Saved|Error)=0 as param
    let notifOnSaved = true;
    let notifOnError = true;
    if (!isFormData) {
      if (params['notifOnSaved'] === 0) {
        notifOnSaved = false;
      }
      delete params['notifOnSaved'];

      if (params['notifOnError'] === 0) {
        notifOnError = false;
      }
      delete params['notifOnError'];
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
      options.body = isFormData ? params : JSON.stringify(params);
    }

    let urlParams = '';
    if (method === Method.GET && !isFormData && Object.keys(params).length > 0) {
      urlParams = `?${new URLSearchParams(params as Record<string, string>).toString()}`;
    }

    return fetch(`api/v2/${query}${urlParams}`, options).then(async response => {
      if (response.status !== this.getOkStatusFromMethod(method)) {
        return response.json().then(json => {
          const error = new Error(json.message || json.description) as Error & { status?: number };
          error.status = response.status;
          throw error;
        });
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

      const wrappedError = new Error(error.message) as Error & { status?: number };
      wrappedError.status = error.status;
      return Promise.reject(wrappedError);
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
