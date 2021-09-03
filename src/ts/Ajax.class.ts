/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import { Payload, Action, Method, ResponseMsg } from './interfaces';

export class Ajax {
  type: string;
  id: string;
  controller: string;

  constructor(type = 'experiments', id = '0', controller = '') {
    this.type = type;
    this.id = id;
    this.controller = controller;
    if (controller === '') {
      this.controller = 'app/controllers/EntityAjaxController.php';
    }
  }

  /** @deprecated */
  get(action: string): Promise<ResponseMsg> {
    return fetch(`${this.controller}?${action}=1&id=${this.id}&type=${this.type}`).then(response => {
      if (!response.ok) {
        throw new Error('An unexpected error occured!');
      }
      return response.json();
    }).then(json => {
      if (!json.res) {
        notif(json);
        throw new Error('An unexpected error occured!');
      }
      return json;
    });
  }

  /** @deprecated */
  post(action: string): Promise<ResponseMsg> {
    const formData = new FormData();
    formData.append(action, '1');
    formData.append('type', this.type);
    formData.append('id', this.id);
    formData.append('csrf', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    return fetch(this.controller, {
      method: 'POST',
      body: formData,
    }).then(response => {
      if (!response.ok) {
        throw new Error('An unexpected error occured!');
      }
      return response.json();
    }).then(json => {
      if (!json.res) {
        notif(json);
        throw new Error('An unexpected error occured!');
      }
      return json;
    });
  }

  send(payload: Payload): Promise<ResponseMsg> {
    // get request should not have a body, and that's a shame, it would make things simpler IMHO..
    let response: Promise<Response>;
    if (payload.method === Method.GET) {
      response = this.sendGet(payload);
    } else if (payload.method === Method.UNAUTHGET) {
      response = this.sendUnauthGet(payload);
    } else {
      response = this.sendPost(payload);
    }
    return response.then(response => {
      if (!response.ok) {
        throw new Error('An unexpected error occured!');
      }
      if (response.headers.has('X-Elab-Need-Auth')) {
        notif({res: false, msg: 'Your session expired!'});
        throw new Error('Session expired!');
      }
      return response.json();
    }).then(json => {
      // we don't want notifs on get requests or create requests
      if (payload.method === Method.POST && payload.action !== Action.Create) {
        notif(json);
      }
      return json;
    });
  }

  sendPost(payload: Payload): Promise<Response> {
    // now doing POST method
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    return fetch('app/controllers/RequestHandler.php', {
      method: payload.method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(payload),
    });
  }

  sendGet(payload: Payload): Promise<Response> {
    // encode the json in a percent encoded parameter
    const encoded = encodeURIComponent(JSON.stringify(payload));
    // p as in payload
    return fetch(`app/controllers/RequestHandler.php?p=${encoded}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
  }

  sendUnauthGet(payload: Payload): Promise<Response> {
    // encode the json in a percent encoded parameter
    const encoded = encodeURIComponent(JSON.stringify(payload));
    // p as in payload
    return fetch(`app/controllers/UnauthRequestHandler.php?p=${encoded}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
  }
}
