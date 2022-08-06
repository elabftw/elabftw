/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Method } from './interfaces';
import { notifSaved, notifError } from './misc';

export class Api {
  send(query: string, method: Method, params = {}): Promise<Response>
  {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    return fetch(`api/v2/${query}`, {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(params),
    }).then(response => {
      if (response.status !== this.getOkStatusFromMethod(method)) {
        throw new Error('An unexpected error occurred!');
      }
      return response;
    }).then(response => {
      notifSaved();
      return response;
    }).catch(error => {
      notifError(error);
      return new Promise((resolve, reject) => reject(new Error(error.message)));
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
