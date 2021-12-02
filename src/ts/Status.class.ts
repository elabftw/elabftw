/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Payload, Method, Model, Action, ResponseMsg } from './interfaces';
import { Ajax } from './Ajax.class';

export default class Status {
  model: Model;
  sender: Ajax;

  constructor() {
    this.model = Model.Status,
    this.sender = new Ajax();
  }

  create(content: string, color: string, isTimestampable: boolean): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Create,
      model: this.model,
      content: content,
      extraParams: {
        color: color,
        isTimestampable: isTimestampable,
      },
      notif: true,
    };
    return this.sender.send(payload);
  }

  update(id: number, content: string, color: string, isTimestampable: boolean, isDefault: boolean): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Update,
      model: this.model,
      content: content,
      id : id,
      extraParams: {
        color: color,
        isTimestampable: isTimestampable,
        isDefault: isDefault,
      },
      notif: true,
    };
    return this.sender.send(payload);
  }

  destroy(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Destroy,
      model: this.model,
      id : id,
      notif: true,
    };
    return this.sender.send(payload);
  }
}
