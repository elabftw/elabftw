/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Payload, Type, Method, Model, Action, ResponseMsg } from './interfaces';
import { Ajax } from './Ajax.class';


export default class Experiment {

  model: Model;
  sender: Ajax;

  constructor() {
    this.model = Model.Item,
    this.sender = new Ajax();
  }

  create(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Create,
      model: this.model,
      id: id,
      entity: {
        type: Type.Item,
        id: null,
      }
    };
    return this.sender.send(payload);
  }

}
