/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Payload, Method, Model, Action, ResponseMsg } from './interfaces';
import { Ajax } from './Ajax.class';

export default class Apikey {
  model: Model;
  sender: Ajax;

  constructor() {
    this.model = Model.Apikey,
    this.sender = new Ajax();
  }

  create(content: string, canwrite: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Create,
      model: this.model,
      content: content,
      extraParams: {
        canwrite: canwrite,
      },
    };
    return this.sender.send(payload);
  }

  destroy(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Destroy,
      model: this.model,
      id : id,
    };
    return this.sender.send(payload);
  }
}
