/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { notif } from './misc';
import { ActionReq } from './interfaces';

export default class Crud {
  controller: string;

  constructor(controller: string) {
    this.controller = controller;
  }

  send(req: ActionReq): JQueryPromise<any> {
    return $.ajax({
      type: 'post',
      url: this.controller,
      data: req,
      success: function(response): void {
        notif(response);
      },
      error: function(): void {
        notif({ 'res': false, 'msg': 'Error processing request!' });
      },
    });
  }
}
