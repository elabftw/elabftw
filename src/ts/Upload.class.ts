/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Payload, Method, Model, Entity, Action, Target, ResponseMsg } from './interfaces';
import { Ajax } from './Ajax.class';

export default class Upload {
  entity: Entity;
  model: Model;
  sender: Ajax;

  constructor(entity: Entity) {
    this.entity = entity;
    this.model = Model.Upload,
    this.sender = new Ajax();
  }

  read(): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.GET,
      action: Action.Read,
      model: this.model,
      entity: this.entity,
      target: Target.All,
    };
    return this.sender.send(payload);
  }

  update(content: string, id: number, target: Target): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Update,
      model: this.model,
      target: target,
      entity: this.entity,
      content: content,
      id : id,
    };
    return this.sender.send(payload);
  }

  destroy(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Destroy,
      model: this.model,
      entity: this.entity,
      id : id,
    };
    return this.sender.send(payload);
  }
}
