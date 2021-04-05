/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Payload, Method, Model, Target, Entity, Action } from './interfaces';
import { Ajax } from './Ajax.class';

export default class Apikey {
  model: Model;
  sender: Ajax;

  constructor() {
    this.model = Model.Apikey,
    this.sender = new Ajax();
  }

  create(content: string) {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Create,
      model: this.model,
    };
    return this.sender.send(payload);
  }

  destroy(id: number) {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Destroy,
      model: this.model,
      id : id,
    };
    return this.sender.send(payload);
  }
}
