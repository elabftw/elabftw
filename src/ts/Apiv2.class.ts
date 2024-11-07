/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Method } from './interfaces';
import { notifSaved, notifError, getNewIdFromPostRequest } from './misc';

export class Api {
  // set this to false to prevent the "Saved" notification from showing up
  notifOnSaved = true;
  notifOnError = true;

  get(query: string): Promise<Response> {
    return this.send(Method.GET, query);
  }

  getJson(query: string) {
    return this.get(query).then(resp => resp.json());
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
      console.error('Error fetching the file:', error);
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
    };
    if ([Method.POST, Method.PATCH].includes(method)) {
      options['body'] = JSON.stringify(params);
    }
    return fetch(`api/v2/${query}`, options).then(response => {
      if (response.status !== this.getOkStatusFromMethod(method)) {
        // if there is an error we will get the message in the reply body
        return response.json().then(json => { throw new Error(json.description); });
      }
      return response;
    }).then(response => {
      if (method !== Method.GET && this.notifOnSaved) {
        notifSaved();
      }
      return response;
    }).catch(error => {
      if (this.notifOnError) {
        notifError(error);
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
