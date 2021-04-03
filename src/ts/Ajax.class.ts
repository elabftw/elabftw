/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import { ResponseMsg } from './interfaces';

export class Ajax {
  type: string;
  id: string;
  controller: string;

  constructor(type: string, id: string, controller = '') {
    this.type = type;
    this.id = id;
    this.controller = controller;
    if (controller === '') {
      this.controller = 'app/controllers/EntityAjaxController.php';
    }
  }

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

  do(action: string): Promise<ResponseMsg> {
    // note: only works on Ajax.php controller
    return fetch(`${this.controller}?action=${action}&what=${this.type}`).then(response => {
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
}
