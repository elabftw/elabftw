/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Method } from './interfaces';
import { getNewIdFromPostRequest } from './misc';
import { notify } from './notify';

export class Api {
  // set this to false to prevent the "Saved" notification from showing up
  notifOnSaved = true;
  notifOnError = true;
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

  patch(query: string, params = {}): Promise<Response> {
    return this.send(Method.PATCH, query, params);
  }

  post(query: string, params = {}): Promise<Response> {
    return this.send(Method.POST, query, params);
  }

  post2location(query: string, params = {}): Promise<number> {
    return this.send(Method.POST, query, params).then(res => getNewIdFromPostRequest(res));
  }


  delete(query: string): Promise<Response> {
    return this.send(Method.DELETE, query);
  }

  // private method: use patch/post/delete instead
  send(method: Method, query: string, params = {}): Promise<Response> {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const options = {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      keepalive: this.keepalive,
    };
    if ([Method.POST, Method.PATCH].includes(method)) {
      options['body'] = JSON.stringify(params);
    }
    let urlParams = '';
    if (method === Method.GET && Object.keys(params).length > 0) {
      urlParams = `?${new URLSearchParams(params).toString()}`;
    }
    return fetch(`api/v2/${query}${urlParams}`, options).then(async response => {
      if (response.status !== this.getOkStatusFromMethod(method)) {
        // if there is an error we will get the message in the reply body
        return response.json().then(json => { throw new Error(json.message || json.description); });
      }
      return response;
    }).then(response => {
      if (method !== Method.GET && this.notifOnSaved) {
        notify.success();
      }
      return response;
    }).catch(error => {
      if (this.notifOnError) {
        notify.error(error.message);
      }
      return Promise.reject(new Error(error.message));
    });
  }

  getOkStatusFromMethod(method: Method): number {
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
